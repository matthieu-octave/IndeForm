<?php

return [
    /*
    | Inscription publique. Fermée par défaut pendant la bêta restreinte.
    | - REGISTRATION_OPEN=true : tout le monde peut créer un compte.
    | - REGISTRATION_INVITE_CODE=... : l'inscription reste possible avec ce code d'invitation.
    | Les comptes peuvent aussi être créés en SSH : php artisan compte:create-user
    */
    'registration_open' => (bool) env('REGISTRATION_OPEN', false),
    'invite_code' => env('REGISTRATION_INVITE_CODE') ?: null,

    /* Modules de la suite (catalogue provisoire, remplacé par la table modules au lot B). */
    'modules' => [
        ['code' => 'pilot', 'name' => 'Form’Pilot', 'url' => env('MODULE_PILOT_URL', 'https://pilot-indeform.mowd.fr'),
            'description' => 'Organismes, missions, planning, heures et préparation de la facturation.'],
        ['code' => 'studio', 'name' => 'Form’Studio', 'url' => env('MODULE_STUDIO_URL', 'https://studio-indeform.mowd.fr'),
            'description' => 'Préparation des formations : modules, séances, déroulés et supports.'],
    ],

    /* Éditeur du service (mentions légales et politique de confidentialité). */
    'publisher' => [
        'name' => 'Matthieu Octave',
        'trade_name' => 'MO Web Dev',
        'status' => 'Entrepreneur individuel (micro-entreprise)',
        'siret' => '799 232 715 00026',
        'address' => '19 place Jeanne d’Arc, 57175 Gandrange, France',
        'email' => 'contact@mowd.fr',
        'director' => 'Matthieu Octave',
    ],

    'host' => [
        'name' => 'PlanetHoster inc.',
        'address' => '4416 Louis-B.-Mayer, Laval (Québec) H7P 0G1, Canada',
        'phone' => '+33 1 76 60 41 43',
        'website' => 'https://www.planethoster.com',
        'data_location' => 'en France (centre de données de Paris)',
    ],

    /* Durée de conservation des sauvegardes de la base (jours), voir backup.sh. */
    'backup_days' => 14,
];
