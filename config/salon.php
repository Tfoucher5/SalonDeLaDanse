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
    | Equipe organisatrice
    |--------------------------------------------------------------------------
    |
    | Coordonnees affichees sur le dashboard benevole. Elles vivent dans .env
    | pour rester modifiables d une edition a l autre sans toucher au code ;
    | une valeur absente est simplement masquee a l ecran.
    |
    */

    'contact' => [
        'name' => env('SALON_CONTACT_NAME'),
        'email' => env('SALON_CONTACT_EMAIL'),
        'phone' => env('SALON_CONTACT_PHONE'),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Jauges de remplissage
    |--------------------------------------------------------------------------
    |
    | Part des places encore libres en dessous de laquelle un creneau passe en
    | ambre : « presque complet ». Avec une jauge de 4 places, 0,25 bascule le
    | creneau quand il n en reste plus qu une.
    |
    */

    'gauge' => [
        'tight_ratio' => (float) env('SALON_GAUGE_TIGHT_RATIO', 0.25),
    ],

    'seed' => [
        'default_shift_capacity' => (int) env('SALON_DEFAULT_SHIFT_CAPACITY', 4),
        'invitation_codes' => (int) env('SALON_SEED_INVITATION_CODES', 20),
    ],

];
