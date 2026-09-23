@props(['value' => null])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-zinc-500']) }}>
    {{ $value ?? $slot }}
</label>
