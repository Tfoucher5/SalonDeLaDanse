@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-zinc-900']) }}>
    {{ $value ?? $slot }}
</label>
