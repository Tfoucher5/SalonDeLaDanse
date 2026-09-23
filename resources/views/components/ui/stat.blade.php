{{-- Un chiffre et ce qu'il compte. Utilise sur le dashboard et en back-office. --}}

@props([
    'label',
    'value',
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-zinc-100/70 p-4 tabular-grid']) }}>
    <p class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">{{ $label }}</p>
    <p class="mt-1 text-base font-bold text-zinc-900">{{ $value }}</p>

    @if ($hint !== null)
        <p class="mt-0.5 text-sm text-zinc-500">{{ $hint }}</p>
    @endif
</div>
