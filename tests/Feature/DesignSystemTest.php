<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

/**
 * @return array<int, string>
 */
function bladeViews(): array
{
    return collect(File::allFiles(resource_path('views')))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->map(fn ($file) => $file->getPathname())
        ->values()
        ->all();
}

/**
 * Les vues qui enfreignent un motif, nommees relativement au dossier des vues.
 *
 * @param  array<int, string>  $except  Chemins relatifs explicitement autorises.
 * @return array<int, string>
 */
function viewsMatching(string $pattern, array $except = []): array
{
    return collect(bladeViews())
        ->filter(fn (string $path): bool => preg_match($pattern, File::get($path)) === 1)
        ->map(fn (string $path): string => str_replace(
            [resource_path('views').DIRECTORY_SEPARATOR, '\\'], ['', '/'], $path
        ))
        ->reject(fn (string $view): bool => in_array($view, $except, true))
        ->values()
        ->all();
}

it('ne garde aucune classe de mode sombre dans les vues', function () {
    expect(viewsMatching('/dark:/'))->toBe([]);
});

it('n utilise aucune palette Tailwind hors zinc et tokens du design system', function () {
    // Les neutres passent par zinc, la couleur passe par primary, plum, gauge et danger.
    $forbidden = '/\b(?:bg|text|border|ring|fill|from|to|via|divide|placeholder|accent|outline)-(gray|slate|neutral|stone|indigo|red|green|emerald|amber|orange|yellow|blue|violet|purple|pink|teal|cyan|lime|rose|sky|fuchsia)-\d{2,3}\b/';

    expect(viewsMatching($forbidden))->toBe([]);
});

it('n ecrit aucune couleur en dur dans les vues', function () {
    // Les hexadecimaux de la charte vivent dans tailwind.config.js et dans le
    // controleur de la planche de reference, jamais dans une vue.
    expect(viewsMatching('/#[0-9A-Fa-f]{6}\b/', except: ['components/layout/head.blade.php']))->toBe([]);
});

it('ne met jamais un bouton ou un champ en capsule', function () {
    // La capsule `rounded-full` est reservee aux badges, pastilles et avatars :
    // boutons et champs restent en `rounded-xl`, cartes en `rounded-2xl`.
    expect(viewsMatching('/<(?:button|input|select|textarea)\b[^>]*\brounded-full\b/'))->toBe([]);
});

it('n utilise que les ombres nommees de la charte', function () {
    // Les ombres de la charte sont teintees prune et portent un nom ; les
    // ombres generiques de Tailwind, grises, saliraient la surface porcelaine.
    expect(viewsMatching('/\bshadow-(?!(?:card|lift|cta|overlay)\b)[a-z0-9-]+/'))->toBe([]);

    expect(viewsMatching('/\bshadow-overlay\b/'))
        ->toBe(['components/dropdown.blade.php', 'components/modal.blade.php', 'components/ui/toast.blade.php']);
});

it('declare les tokens de la charte dans la configuration Tailwind', function () {
    $config = File::get(base_path('tailwind.config.js'));

    expect($config)->toContain("'#B93A24'")   // primary
        ->and($config)->toContain("'#9A2C19'") // primary-hover
        ->and($config)->toContain("'#E0533C'") // primary-bright, terracotta de marque
        ->and($config)->toContain("'#FDEBE7'") // primary-soft
        ->and($config)->toContain("'#6C2E58'") // plum
        ->and($config)->toContain("'#0F766E'") // gauge-free
        ->and($config)->toContain("'#B45309'") // gauge-tight
        ->and($config)->toContain("'#716B70'") // gauge-full
        ->and($config)->toContain("'#B91C1C'") // danger
        ->and($config)->toContain('Plus Jakarta Sans')
        ->and($config)->toContain('overlay')
        ->and($config)->toContain('touch');    // la cible tactile de 44 px
});

it('charge Plus Jakarta Sans depuis un seul en-tete et abandonne Figtree', function () {
    $head = File::get(resource_path('views/components/layout/head.blade.php'));

    expect($head)->toContain('family=Plus+Jakarta+Sans');

    // Tout `<head>` de l application passe par ce partiel : une seule
    // declaration de police, un seul point d entree Vite.
    foreach (['layouts/app', 'layouts/guest'] as $layout) {
        expect(File::get(resource_path('views/'.$layout.'.blade.php')))->toContain('<x-layout.head');
    }

    expect(viewsMatching('/figtree/i'))->toBe([])
        ->and(viewsMatching('/family=Plus\+Jakarta\+Sans/'))->toBe(['components/layout/head.blade.php']);
});

it('active les chiffres tabulaires sur les grilles', function () {
    expect(File::get(resource_path('css/app.css')))->toContain('font-variant-numeric: tabular-nums');
});

it('publie la planche de reference hors production', function () {
    $this->actingAs(User::factory()->create())
        ->get('/design-system')
        ->assertOk()
        ->assertSee('Charte graphique');
});

it('declare un favicon qui suit le theme clair ou sombre', function () {
    $head = File::get(resource_path('views/components/layout/head.blade.php'));

    expect($head)->toContain("asset('favicon.svg')")
        ->and($head)->toContain("asset('apple-touch-icon.png')")
        ->and(File::get(public_path('favicon.svg')))->toContain('prefers-color-scheme: dark');
});
