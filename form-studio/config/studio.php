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
];
