{{--
    En-tete HTML commun a toutes les pages : une seule declaration de police,
    un seul point d'entree Vite. Tout `<head>` de l'application passe par ici.
--}}

@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#FAFAFA">

<title>{{ $title !== null ? $title.' — '.config('app.name') : config('app.name') }}</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
