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
    | Mentions legales
    |--------------------------------------------------------------------------
    |
    | L editeur est l association JayDance Fam, organisatrice du Salon : ses
    | identifiants sont publics (registre SIRENE, repertoire des associations).
    | L hebergeur depend du deploiement et se renseigne donc dans .env.
    |
    */

    'legal' => [
        'publisher' => [
            'name' => env('SALON_LEGAL_PUBLISHER_NAME', 'JayDance Fam'),
            'status' => 'Association déclarée, régie par la loi du 1er juillet 1901',
            'address' => env('SALON_LEGAL_PUBLISHER_ADDRESS', 'Decathlon, avenue du Moulin Marcille, 49130 Les Ponts-de-Cé'),
            'siren' => env('SALON_LEGAL_PUBLISHER_SIREN', '877 993 584'),
            'siret' => env('SALON_LEGAL_PUBLISHER_SIRET', '877 993 584 00029'),
            'rna' => env('SALON_LEGAL_PUBLISHER_RNA', 'W491019569'),
            'director' => env('SALON_LEGAL_PUBLICATION_DIRECTOR', 'Sandra Cailleau, à la présidence de l\'association'),
            'email' => env('SALON_LEGAL_PUBLISHER_EMAIL', 'salondeladanse49@gmail.com'),
            'phone' => env('SALON_LEGAL_PUBLISHER_PHONE', '02 41 93 83 77'),
        ],
        'host' => [
            'name' => env('SALON_LEGAL_HOST_NAME'),
            'address' => env('SALON_LEGAL_HOST_ADDRESS'),
            'website' => env('SALON_LEGAL_HOST_WEBSITE'),
        ],
        'data_retention_months' => (int) env('SALON_LEGAL_DATA_RETENTION_MONTHS', 12),
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
        // Benevoles fictifs de VolunteerSeeder, a lancer a part en developpement.
        'volunteers' => (int) env('SALON_SEED_VOLUNTEERS', 100),
    ],

];
