<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Mots de passe : 10 caractères minimum ; en production, refus des mots de passe connus dans des fuites.
        Password::defaults(function () {
            $rule = Password::min(10);

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Indé’Form : réinitialiser votre mot de passe')
                ->greeting('Bonjour '.$user->name.',')
                ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte Indé’Form.')
                ->action('Choisir un nouveau mot de passe', $url)
                ->line('Ce lien est valable 60 minutes.')
                ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail : votre mot de passe reste inchangé.')
                ->salutation('L’équipe Indé’Form');
        });

        VerifyEmail::toMailUsing(function ($user, string $url) {
            return (new MailMessage)
                ->subject('Indé’Form : confirmez votre adresse e-mail')
                ->greeting('Bonjour '.$user->name.',')
                ->line('Merci de confirmer que cette adresse e-mail est bien la vôtre : elle sert à vous connecter à tous les modules Indé’Form.')
                ->action('Confirmer mon adresse', $url)
                ->line('Ce lien est valable 60 minutes. Si vous n’avez pas créé de compte, ignorez cet e-mail.')
                ->salutation('L’équipe Indé’Form');
        });
    }
}
