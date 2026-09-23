{{--
    Photo du benevole, ou ses initiales a defaut, dans une pastille ronde.

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
         {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover ring-2 ring-white '.$size]) }}>
@else
    <span aria-hidden="true"
          {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white ring-2 ring-white '.$size]) }}>
        {{ $user->initials }}
    </span>
@endif
