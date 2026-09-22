<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Compte administrateur de developpement
    |--------------------------------------------------------------------------
    |
    | Ces identifiants viennent exclusivement de .env : aucun secret ne doit
    | apparaitre dans un fichier versionne. Sans e-mail ni mot de passe,
    | AdminSeeder se contente d un avertissement et ne cree rien.
    |
    */

    'admin' => [
        'first_name' => env('SALON_ADMIN_FIRST_NAME', 'Admin'),
        'last_name' => env('SALON_ADMIN_LAST_NAME', 'Salon'),
        'email' => env('SALON_ADMIN_EMAIL'),
        'phone' => env('SALON_ADMIN_PHONE', ''),
        'password' => env('SALON_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Valeurs de seed
    |--------------------------------------------------------------------------
    |
    | La jauge par defaut s applique a chaque creneau cree par ShiftSeeder ;
    | elle reste modifiable creneau par creneau depuis le back-office.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Photo du benevole
    |--------------------------------------------------------------------------
    |
    | La photo est obligatoire a l inscription et stockee sur le disque public.
    |
    */

    'photo' => [
        'directory' => 'volunteers/photos',
        'max_kilobytes' => (int) env('SALON_PHOTO_MAX_KILOBYTES', 4096),
        'mimes' => ['jpeg', 'jpg', 'png', 'webp'],
    ],

    'seed' => [
        'default_shift_capacity' => (int) env('SALON_DEFAULT_SHIFT_CAPACITY', 4),
        'invitation_codes' => (int) env('SALON_SEED_INVITATION_CODES', 20),
    ],

];
