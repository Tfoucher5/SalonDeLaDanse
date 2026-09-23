{{--
    Un champ complet : libelle, controle, aide, erreur. Une erreur affiche
    toujours un message sous le champ — jamais une simple coloration, le
    message dit quoi corriger.
--}}

@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'messages' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label !== null)
        <x-input-label :for="$for">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </x-input-label>
    @endif

    {{ $slot }}

    @if ($hint !== null)
        <p class="text-sm text-zinc-500">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$messages" />
</div>
