{{--
    Liste deroulante. Meme trait, meme hauteur et meme anneau de focus qu'un
    champ texte : un formulaire de recherche doit s'aligner au pixel.
--}}

@props(['disabled' => false])

<select @disabled($disabled)
    {{ $attributes->merge(['class' => 'h-10 w-full rounded-md border-zinc-200 text-sm text-zinc-900 focus:border-primary focus:ring-2 focus:ring-primary-ring disabled:bg-zinc-50 disabled:text-zinc-400']) }}>
    {{ $slot }}
</select>
