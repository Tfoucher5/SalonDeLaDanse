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
    // Les neutres passent par zinc, la couleur passe par primary, gauge et danger.
    $forbidden = '/\b(?:bg|text|border|ring|fill|from|to|via|divide|placeholder|accent|outline)-(gray|slate|neutral|stone|indigo|red|green|emerald|amber|orange|yellow|blue|violet|purple|pink|teal|cyan|lime|rose|sky|fuchsia)-\d{2,3}\b/';

    expect(viewsMatching($forbidden))->toBe([]);
});

it('n ecrit aucune couleur en dur dans les vues', function () {
    // Les hexadecimaux de la charte vivent dans tailwind.config.js et dans le
    // controleur de la planche de reference, jamais dans une vue.
    expect(viewsMatching('/#[0-9A-Fa-f]{6}\b/', except: ['components/layout/head.blade.php']))->toBe([]);
});

it('bannit les pilules, signature de la charte abandonnee', function () {
    expect(viewsMatching('/\brounded-full\b/'))->toBe([]);
});

it('reserve l ombre unique aux elements qui flottent vraiment', function () {
    // Une seule ombre existe, `shadow-overlay`, et seuls le menu deroulant et
    // les modales y ont droit. Empiler les ombres dans une grille dense la salit.
    expect(viewsMatching('/\bshadow-(?!overlay\b)[a-z0-9-]+/'))->toBe([]);

    expect(viewsMatching('/\bshadow-overlay\b/'))->toBe([
        'components/dropdown.blade.php',
        'components/modal.blade.php',
        'components/ui/confirm-form.blade.php',
    ]);
});

it('declare les tokens de la charte dans la configuration Tailwind', function () {
    $config = File::get(base_path('tailwind.config.js'));

    expect($config)->toContain("'#4338CA'")   // primary
        ->and($config)->toContain("'#3730A3'") // primary-hover
        ->and($config)->toContain("'#4F46E5'") // primary-ring
        ->and($config)->toContain("'#EEF2FF'") // primary-soft
        ->and($config)->toContain("'#15803D'") // gauge-free
        ->and($config)->toContain("'#B45309'") // gauge-tight
        ->and($config)->toContain("'#71717A'") // gauge-full
        ->and($config)->toContain("'#B91C1C'") // danger
        ->and($config)->toContain("'Inter'")
        ->and($config)->toContain('overlay')   // l ombre unique
        ->and($config)->toContain('touch');    // la cible tactile de 44 px
});

it('charge Inter depuis un seul en-tete et abandonne Figtree', function () {
    $head = File::get(resource_path('views/components/layout/head.blade.php'));

    expect($head)->toContain('family=Inter');

    // Tout `<head>` de l application passe par ce partiel : une seule
    // declaration de police, un seul point d entree Vite.
    foreach (['layouts/app', 'layouts/guest'] as $layout) {
        expect(File::get(resource_path('views/'.$layout.'.blade.php')))->toContain('<x-layout.head');
    }

    expect(viewsMatching('/figtree/i'))->toBe([])
        ->and(viewsMatching('/family=Inter/'))->toBe(['components/layout/head.blade.php']);
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
