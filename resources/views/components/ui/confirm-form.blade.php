{{--
    Un bouton qui n'agit qu'une fois confirmé.

    Reserve aux ecritures qu'on ne defait pas d'un clic : reinitialiser un mot
    de passe, retirer un creneau, rouvrir un planning valide. La modale est le
    seul endroit de l'interface ou une ombre est admise — c'est ce qui la
    detache vraiment de la page.
--}}

@props([
    'action',
    'method' => 'post',
    'title',
    'confirm' => 'Confirmer',
    'variant' => 'secondary',
    'size' => 'md',
])

<div x-data="{ open: false }" {{ $attributes->only('class') }}>
    <x-ui.button type="button" :variant="$variant" :size="$size" x-on:click="open = true">
        {{ $slot }}
    </x-ui.button>

    <div x-show="open"
         x-cloak
         x-on:keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
         role="dialog"
         aria-modal="true">
        <div x-show="open" x-transition.opacity
             x-on:click="open = false"
             class="absolute inset-0 bg-zinc-900/40"
             aria-hidden="true"></div>

        <div x-show="open" x-transition
             class="relative w-full max-w-md rounded-lg border border-zinc-200 bg-white p-5 shadow-overlay sm:p-6">
            <h2 class="text-lg font-semibold text-zinc-900">{{ $title }}</h2>

            @isset($body)
                <div class="mt-2 text-sm text-zinc-500">{{ $body }}</div>
            @endisset

            <form method="POST" action="{{ $action }}" class="mt-5 flex flex-wrap justify-end gap-2">
                @csrf
                @unless ($method === 'post')
                    @method($method)
                @endunless

                <x-ui.button type="button" variant="ghost" size="touch" x-on:click="open = false">
                    Annuler
                </x-ui.button>

                <x-ui.button type="submit" :variant="$variant === 'ghost' ? 'secondary' : $variant" size="touch">
                    {{ $confirm }}
                </x-ui.button>
            </form>
        </div>
    </div>
</div>
