<?php

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

it('ne garde aucune classe de mode sombre dans les vues', function () {
    $offenders = collect(bladeViews())
        ->filter(fn (string $path) => str_contains(File::get($path), 'dark:'))
        ->map(fn (string $path) => str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

it('n utilise aucune palette Tailwind hors zinc et tokens du design system', function () {
    // Les neutres passent par zinc, la couleur passe par primary, gauge et danger.
    $forbidden = '/\b(?:bg|text|border|ring|fill|from|to|via|divide|placeholder|accent|outline)-(gray|slate|neutral|stone|indigo|red|green|emerald|amber|orange|yellow|blue|violet|purple|pink|teal|cyan|lime|rose|sky|fuchsia)-\d{2,3}\b/';

    $offenders = collect(bladeViews())
        ->filter(fn (string $path) => preg_match($forbidden, File::get($path)) === 1)
        ->map(fn (string $path) => str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $path))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
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
        ->and($config)->toContain("'Inter'");
});

it('charge Inter et abandonne Figtree', function () {
    $layouts = ['layouts/app', 'layouts/guest', 'welcome'];

    foreach ($layouts as $layout) {
        $content = File::get(resource_path('views/'.$layout.'.blade.php'));

        expect($content)->toContain('family=Inter')
            ->and(strtolower($content))->not->toContain('figtree');
    }
});

it('active les chiffres tabulaires sur les grilles', function () {
    expect(File::get(resource_path('css/app.css')))->toContain('font-variant-numeric: tabular-nums');
});
