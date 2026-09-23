{{--
    Logotype officiel du Salon, fourni en blanc sur transparent.

    Il sert de masque plutot que d'image : la couleur vient de `currentColor`,
    donc d'une classe `text-*` du design system. Le meme fichier s'ecrit ainsi
    en encre sur la porcelaine, et en blanc sur un bandeau colore.
--}}

<span role="img" aria-label="{{ config('app.name') }}"
      style="-webkit-mask: url('{{ asset('images/logo-salon-de-la-danse.png') }}') center / contain no-repeat; mask: url('{{ asset('images/logo-salon-de-la-danse.png') }}') center / contain no-repeat;"
      {{ $attributes->merge(['class' => 'inline-block aspect-[1350/726] shrink-0 bg-current text-zinc-900']) }}></span>
