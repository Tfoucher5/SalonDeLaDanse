{{--
    Bouton de retour vers la page parente : une capsule bien visible, une
    fleche et le nom de la destination. Sur les tres petits ecrans, la fleche
    et « Retour » suffisent ; la destination reste annoncee aux lecteurs d'ecran.
--}}

@props(['href'])

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'group inline-flex h-10 w-fit items-center gap-1.5 rounded-full bg-white pe-4 ps-1.5 text-sm font-semibold text-zinc-900 shadow-card ring-1 ring-zinc-900/10 transition hover:text-primary hover:ring-primary/30 print-hidden']) }}>
    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-100 transition group-hover:-translate-x-0.5 group-hover:bg-primary-soft" aria-hidden="true">
        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.7 4.3a1 1 0 010 1.4L8.4 10l4.3 4.3a1 1 0 01-1.4 1.4l-5-5a1 1 0 010-1.4l5-5a1 1 0 011.4 0z" clip-rule="evenodd" />
        </svg>
    </span>
    <span class="max-[359px]:hidden">{{ $slot }}</span>
    <span class="hidden max-[359px]:inline">Retour<span class="sr-only"> : {{ $slot }}</span></span>
</a>
