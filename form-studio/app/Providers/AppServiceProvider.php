<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // E-mail de réinitialisation du mot de passe, en français.
        ResetPassword::toMailUsing(function ($user, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Form’Studio : réinitialiser votre mot de passe')
                ->greeting('Bonjour '.$user->name.',')
                ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte Form’Studio.')
                ->action('Choisir un nouveau mot de passe', $url)
                ->line('Ce lien est valable 60 minutes.')
                ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail : votre mot de passe reste inchangé.')
                ->salutation('L’équipe Indé’Form');
        });
    }
}
