@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'h-10 w-full rounded-md border-zinc-200 text-sm text-zinc-900 placeholder-zinc-400 focus:border-primary focus:ring-2 focus:ring-primary-ring disabled:bg-zinc-50 disabled:text-zinc-400']) }}>
