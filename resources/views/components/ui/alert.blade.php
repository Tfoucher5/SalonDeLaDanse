{{--
    Message d'issue : ce que l'action vient de produire, ou ce que l'ecran
    impose. `danger` est reserve a l'echec ; un etat normal, meme bloquant,
    reste neutre.
--}}

@props([
    'tone' => 'neutral',
    'title' => null,
])

@php
    $tones = [
        'neutral' => ['surface' => 'border-zinc-200 bg-zinc-100', 'text' => 'text-zinc-500', 'icon' => 'text-zinc-400', 'role' => 'status'],
        'primary' => ['surface' => 'border-primary bg-primary-soft', 'text' => 'text-zinc-900', 'icon' => 'text-primary', 'role' => 'status'],
        'success' => ['surface' => 'border-zinc-200 bg-white', 'text' => 'text-zinc-900', 'icon' => 'text-gauge-free', 'role' => 'status'],
        'attention' => ['surface' => 'border-zinc-200 bg-white', 'text' => 'text-zinc-900', 'icon' => 'text-gauge-tight', 'role' => 'status'],
        'danger' => ['surface' => 'border-danger bg-white', 'text' => 'text-danger', 'icon' => 'text-danger', 'role' => 'alert'],
    ];

    $style = $tones[$tone] ?? $tones['neutral'];

    $paths = [
        'neutral' => 'M10 2a8 8 0 100 16 8 8 0 000-16zm1 4a1 1 0 11-2 0 1 1 0 012 0zm-2 3a1 1 0 012 0v5a1 1 0 11-2 0V9z',
        'primary' => 'M10 2a8 8 0 100 16 8 8 0 000-16zm1 4a1 1 0 11-2 0 1 1 0 012 0zm-2 3a1 1 0 012 0v5a1 1 0 11-2 0V9z',
        'success' => 'M10 2a8 8 0 100 16 8 8 0 000-16zm4 5.7l-5 5a1 1 0 01-1.4 0l-2.3-2.3a1 1 0 011.4-1.4l1.6 1.6 4.3-4.3a1 1 0 011.4 1.4z',
        'attention' => 'M9.1 3.3a1 1 0 011.8 0l6.5 12.2a1 1 0 01-.9 1.5H3.5a1 1 0 01-.9-1.5L9.1 3.3zM10 7a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1zm0 7.5a1 1 0 110-2 1 1 0 010 2z',
        'danger' => 'M9.1 3.3a1 1 0 011.8 0l6.5 12.2a1 1 0 01-.9 1.5H3.5a1 1 0 01-.9-1.5L9.1 3.3zM10 7a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1zm0 7.5a1 1 0 110-2 1 1 0 010 2z',
    ];
@endphp

<div role="{{ $style['role'] }}" {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-md border p-4 text-sm '.$style['surface'].' '.$style['text']]) }}>
    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $style['icon'] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" clip-rule="evenodd" d="{{ $paths[$tone] ?? $paths['neutral'] }}" />
    </svg>

    <div class="min-w-0">
        @if ($title !== null)
            <p class="font-medium text-zinc-900">{{ $title }}</p>
        @endif

        <div @class(['mt-1' => $title !== null])>{{ $slot }}</div>
    </div>
</div>
