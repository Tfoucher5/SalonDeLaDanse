{{-- Un chiffre et ce qu'il compte. Utilise sur le dashboard et en back-office. --}}

@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-4 tabular-grid']) }}>
    <p class="text-sm text-zinc-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-zinc-900">{{ $value }}</p>

    @if ($hint !== null)
        <p class="mt-1 text-sm text-zinc-500">{{ $hint }}</p>
    @endif
</div>
