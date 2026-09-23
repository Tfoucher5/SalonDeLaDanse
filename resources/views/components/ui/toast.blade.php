{{--
    Notification ephemere : l'issue d'une action, sans deplacer la page.

    Elle apparait en bas de l'ecran (au-dessus de la barre d'onglets sur
    mobile) et disparait seule. Un refus reste affiche plus longtemps : une
    regle qui bloque doit pouvoir se lire en entier. Le bouton de fermeture
    reste toujours disponible.
--}}

@props([
    'tone' => 'success',
    'duration' => 4000,
])

@php $isError = $tone === 'danger'; @endphp

<div x-data="{ show: false }"
     x-init="$nextTick(() => show = true); setTimeout(() => show = false, {{ (int) $duration }})"
     x-show="show"
     x-cloak
     x-transition:enter="transition duration-300 ease-out"
     x-transition:enter-start="translate-y-3 opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition duration-200 ease-in"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="translate-y-3 opacity-0"
     role="{{ $isError ? 'alert' : 'status' }}"
     {{ $attributes->merge(['class' => 'pointer-events-auto flex w-full items-start gap-3 rounded-2xl p-4 text-sm shadow-overlay '.($isError ? 'bg-danger text-white' : 'bg-zinc-900 text-white')]) }}>
    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $isError ? 'text-white' : 'text-primary-soft' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        @if ($isError)
            <path fill-rule="evenodd" d="M9.1 3.3a1 1 0 011.8 0l6.5 12.2a1 1 0 01-.9 1.5H3.5a1 1 0 01-.9-1.5L9.1 3.3zM10 7a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1zm0 7.5a1 1 0 110-2 1 1 0 010 2z" clip-rule="evenodd" />
        @else
            <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm4 5.7l-5 5a1 1 0 01-1.4 0l-2.3-2.3a1 1 0 011.4-1.4l1.6 1.6 4.3-4.3a1 1 0 011.4 1.4z" clip-rule="evenodd" />
        @endif
    </svg>

    <p class="min-w-0 flex-1 font-medium">{{ $slot }}</p>

    <button type="button" @click="show = false"
            class="-m-1.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/10 hover:text-white">
        <span class="sr-only">Fermer la notification</span>
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M5.3 5.3a1 1 0 011.4 0L10 8.6l3.3-3.3a1 1 0 111.4 1.4L11.4 10l3.3 3.3a1 1 0 01-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 01-1.4-1.4L8.6 10 5.3 6.7a1 1 0 010-1.4z" />
        </svg>
    </button>
</div>
