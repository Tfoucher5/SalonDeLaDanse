@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-900']) }}>
        {{ $status }}
    </div>
@endif
