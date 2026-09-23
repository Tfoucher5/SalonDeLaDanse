{{--
    Photo du benevole, ou ses initiales a defaut. Rayon 6 px comme le reste :
    la pastille ronde etait la signature de la charte abandonnee.

    Un benevole ne voit jamais que sa propre photo — la confidentialite se joue
    dans la requete, pas ici.
--}}

@props([
    'user',
    'size' => 'h-8 w-8',
])

@if ($user->photo_path)
    <img src="{{ Storage::disk('public')->url($user->photo_path) }}"
         alt=""
         {{ $attributes->merge(['class' => 'shrink-0 rounded-md border border-zinc-200 object-cover '.$size]) }}>
@else
    <span aria-hidden="true"
          {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-md border border-zinc-200 bg-zinc-100 text-xs font-medium text-zinc-500 '.$size]) }}>
        {{ $user->initials }}
    </span>
@endif
