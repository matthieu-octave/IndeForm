<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/', '/modules', '/modules/create'] as $url) {
            $this->get($url)->assertRedirect('/connexion');
        }
        $this->post('/modules', ['title' => 'x'])->assertRedirect('/connexion');
    }

    public function test_registration_is_closed_by_default(): void
    {
        config(['studio.registration_open' => false, 'studio.invite_code' => null]);

        $this->get('/inscription')->assertNotFound();
        $this->post('/inscription', [
            'name' => 'Intrus', 'email' => 'intrus@example.test',
            'password' => 'un-mot-de-passe-long', 'password_confirmation' => 'un-mot-de-passe-long',
        ])->assertNotFound();
        $this->get('/connexion')->assertOk()->assertDontSee('Créer un compte');
        $this->assertSame(0, User::count());
    }

    public function test_registration_with_invite_code(): void
    {
        config(['studio.registration_open' => false, 'studio.invite_code' => 'formateurs-2027']);
        $form = [
            'name' => 'Invitée', 'email' => 'invitee@example.test',
            'password' => 'un-mot-de-passe-long', 'password_confirmation' => 'un-mot-de-passe-long',
        ];

        $this->get('/inscription')->assertOk()->assertSee('Code d’invitation');
        $this->post('/inscription', $form + ['invite_code' => 'mauvais'])->assertSessionHasErrors('invite_code');
        $this->assertGuest();

        $this->post('/inscription', $form + ['invite_code' => 'formateurs-2027'])->assertRedirect('/modules');
        $this->assertAuthenticated();
    }

    public function test_accounts_can_be_created_from_the_command_line(): void
    {
        $this->artisan('studio:create-user', ['email' => 'cli@example.test', '--name' => 'Formateur'])
            ->expectsQuestion('Mot de passe (10 caractères minimum)', 'un-mot-de-passe-long')
            ->assertSuccessful();

        $user = User::firstWhere('email', 'cli@example.test');
        $this->assertNotNull($user->workspace_id);
        $this->post('/connexion', ['email' => 'cli@example.test', 'password' => 'un-mot-de-passe-long'])->assertRedirect('/modules');
    }

    public function test_registration_creates_account_and_its_own_workspace(): void
    {
        config(['studio.registration_open' => true]);
        $this->post('/inscription', [
            'name' => 'Marie Dupont',
            'email' => 'marie@example.test',
            'password' => 'un-mot-de-passe-long',
            'password_confirmation' => 'un-mot-de-passe-long',
        ])->assertRedirect('/modules');

        $user = User::firstWhere('email', 'marie@example.test');
        $this->assertNotNull($user->workspace_id);
        $this->assertSame(1, Workspace::count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_short_password_and_duplicate_email(): void
    {
        config(['studio.registration_open' => true]);
        User::factory()->create(['email' => 'a@example.test']);

        $this->post('/inscription', [
            'name' => 'A', 'email' => 'a@example.test',
            'password' => 'court', 'password_confirmation' => 'court',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'mot-de-passe-solide']);

        $this->post('/connexion', ['email' => $user->email, 'password' => 'faux'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/connexion', ['email' => $user->email, 'password' => 'mot-de-passe-solide'])->assertRedirect('/modules');
        $this->assertAuthenticatedAs($user);

        $this->post('/deconnexion')->assertRedirect('/connexion');
        $this->assertGuest();
    }
}
