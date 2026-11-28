<?php

use App\Actions\CreateAccount;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('studio:create-user {email} {--name=}', function (CreateAccount $createAccount) {
    $name = $this->option('name') ?: $this->ask('Nom');
    $password = $this->secret('Mot de passe (10 caractères minimum)');

    $validator = Validator::make(
        ['name' => $name, 'email' => $this->argument('email'), 'password' => $password],
        [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(10)],
        ],
    );
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = $createAccount($name, $this->argument('email'), $password);
    $this->info("Compte créé pour {$user->email}, avec son propre espace de travail.");

    return 0;
})->purpose('Créer un compte Form’Studio (inscription publique fermée)');
