<?php

return [
    /*
    | Inscription publique. Fermée par défaut : seuls les comptes existants se connectent.
    | - REGISTRATION_OPEN=true : tout le monde peut créer un compte.
    | - REGISTRATION_INVITE_CODE=... : l'inscription reste possible avec ce code d'invitation.
    | Les comptes peuvent aussi être créés en SSH : php artisan studio:create-user
    */
    'registration_open' => (bool) env('REGISTRATION_OPEN', false),
    'invite_code' => env('REGISTRATION_INVITE_CODE') ?: null,

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
        'website' => 'https://www.planethoster.com',
    ],

    /* Durée de conservation des sauvegardes de la base (jours), voir backup.sh. */
    'backup_days' => 14,
];
