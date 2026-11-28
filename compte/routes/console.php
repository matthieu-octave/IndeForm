<?php

use App\Actions\CreateAccount;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

Artisan::command('compte:create-user {email} {--name=} {--type=internal : customer, beta, trial ou internal} {--admin : accès à la console d’administration}', function (CreateAccount $createAccount) {
    $name = $this->option('name') ?: $this->ask('Nom');
    $password = $this->secret('Mot de passe (10 caractères minimum)');

    $validator = Validator::make(
        ['name' => $name, 'email' => $this->argument('email'), 'password' => $password, 'type' => $this->option('type')],
        [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'type' => ['required', Rule::in(User::ACCOUNT_TYPES)],
        ],
    );
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    // Compte créé par l'administrateur : l'adresse est considérée comme vérifiée.
    $user = $createAccount($name, $this->argument('email'), $password, $this->option('type'), verified: true);
    if ($this->option('admin')) {
        $user->forceFill(['is_admin' => true])->save();
        AuditLog::record('admin.granted', $user, $user);
    }
    $this->info("Compte créé pour {$user->email} (type {$user->account_type}".($user->is_admin ? ', administrateur' : '').'), avec son propre espace de travail.');

    return 0;
})->purpose('Créer un compte Indé’Form (inscription publique fermée)');

// Journal d'audit conservé 1 an.
Schedule::call(fn () => AuditLog::where('created_at', '<', now()->subYear())->delete())->daily()->name('purge-audit-logs');
