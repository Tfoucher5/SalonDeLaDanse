{{--
    En-tete HTML commun a toutes les pages : une seule declaration de police,
    un seul point d'entree Vite. Tout `<head>` de l'application passe par ici.
--}}

@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#FAFAF7">

<title>{{ $title !== null ? $title.' — '.config('app.name') : config('app.name') }}</title>

{{-- Favicon : le « D » boucle du logotype. La version SVG change de teinte
     avec le theme clair ou sombre du navigateur ; le PNG sert de repli. --}}
<link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
