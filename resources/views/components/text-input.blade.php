@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'h-12 w-full rounded-xl border-zinc-900/15 bg-white px-4 text-[0.9375rem] text-zinc-900 placeholder-zinc-400 transition focus:border-primary-bright focus:ring-4 focus:ring-primary-bright/15 disabled:bg-zinc-50 disabled:text-zinc-400']) }}>
