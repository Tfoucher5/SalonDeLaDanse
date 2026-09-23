{{--
    Etiquette d'etat. La couleur confirme, le mot informe : un badge sans
    libelle lisible n'existe pas.
--}}

@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'border-zinc-200 bg-white text-zinc-500',
        'primary' => 'border-transparent bg-primary-soft text-primary',
        'primary-outline' => 'border-primary bg-white text-primary',
        'free' => 'border-zinc-200 bg-white text-gauge-free',
        'tight' => 'border-zinc-200 bg-white text-gauge-tight',
        'full' => 'border-transparent bg-zinc-100 text-gauge-full',
        'danger' => 'border-zinc-200 bg-white text-danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-7 items-center gap-1.5 rounded-md border px-2.5 text-sm font-medium '.($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
