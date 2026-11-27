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

    public function test_registration_creates_account_and_its_own_workspace(): void
    {
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
