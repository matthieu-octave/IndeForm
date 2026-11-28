<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Lot 5 : pages légales et mot de passe oublié. */
class Lot5Test extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_public(): void
    {
        $this->get('/mentions-legales')->assertOk()->assertSee('MO Web Dev')->assertSee('799 232 715 00026')->assertSee('PlanetHoster');
        $this->get('/confidentialite')->assertOk()->assertSee('CNIL')->assertSee('14 jours');
        $this->get('/connexion')->assertSee('Mentions légales')->assertSee('Mot de passe oublié');
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'marie@example.test']);

        $this->post('/mot-de-passe-oublie', ['email' => 'marie@example.test'])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token, $user) {
            $token = $n->token;
            $mail = $n->toMail($user);

            return str_contains($mail->subject, 'réinitialiser') && str_contains($mail->actionUrl, '/reinitialiser-mot-de-passe/');
        });

        $this->get("/reinitialiser-mot-de-passe/{$token}?email=marie@example.test")->assertOk()->assertSee('nouveau mot de passe');

        $this->post('/reinitialiser-mot-de-passe', [
            'token' => 'faux', 'email' => 'marie@example.test',
            'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->post('/reinitialiser-mot-de-passe', [
            'token' => $token, 'email' => 'marie@example.test',
            'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertRedirect('/connexion');
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();
        $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@example.test'])
            ->assertSessionHas('status')->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }
}
