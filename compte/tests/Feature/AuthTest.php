<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $form = [
        'name' => 'Marie Dupont', 'email' => 'marie@example.test',
        'password' => 'un-mot-de-passe-long', 'password_confirmation' => 'un-mot-de-passe-long',
    ];

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/', '/compte'] as $url) {
            $this->get($url)->assertRedirect('/connexion');
        }
        $this->put('/compte/profil', ['name' => 'x'])->assertRedirect('/connexion');
    }

    public function test_registration_is_closed_by_default(): void
    {
        config(['compte.registration_open' => false, 'compte.invite_code' => null]);

        $this->get('/inscription')->assertNotFound();
        $this->post('/inscription', $this->form)->assertNotFound();
        $this->get('/connexion')->assertOk()->assertDontSee('Créer un compte');
        $this->assertSame(0, User::count());
    }

    public function test_registration_with_invite_code(): void
    {
        config(['compte.registration_open' => false, 'compte.invite_code' => 'formateurs-2027']);

        $this->get('/inscription')->assertOk()->assertSee('Code d’invitation');
        $this->post('/inscription', $this->form + ['invite_code' => 'mauvais'])->assertSessionHasErrors('invite_code');
        $this->assertGuest();

        $this->post('/inscription', $this->form + ['invite_code' => 'formateurs-2027'])->assertRedirect('/verifier-email');
        $this->assertAuthenticated();
    }

    public function test_registration_creates_account_workspace_and_sends_verification(): void
    {
        Notification::fake();
        config(['compte.registration_open' => true]);

        $this->post('/inscription', $this->form)->assertRedirect('/verifier-email');

        $user = User::firstWhere('email', 'marie@example.test');
        $workspace = $user->currentWorkspace;
        $this->assertNotNull($workspace);
        $this->assertSame(1, Workspace::count());
        $this->assertSame($user->id, $workspace->owner_user_id);
        $this->assertSame('owner', $workspace->users()->first()->pivot->role);
        $this->assertSame('customer', $user->account_type);
        $this->assertFalse($user->is_admin);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_cannot_grant_admin_rights(): void
    {
        config(['compte.registration_open' => true]);

        $this->post('/inscription', $this->form + ['is_admin' => 1, 'account_type' => 'internal']);

        $user = User::firstWhere('email', 'marie@example.test');
        $this->assertFalse($user->is_admin);
        $this->assertSame('customer', $user->account_type);
    }

    public function test_registration_rejects_short_password_and_duplicate_email(): void
    {
        config(['compte.registration_open' => true]);
        User::factory()->create(['email' => 'a@example.test']);

        $this->post('/inscription', [
            'name' => 'A', 'email' => 'a@example.test',
            'password' => 'court', 'password_confirmation' => 'court',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_records_last_login_and_audit_then_logout(): void
    {
        $user = User::factory()->create(['password' => 'mot-de-passe-solide']);

        $this->post('/connexion', ['email' => $user->email, 'password' => 'faux'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'login.failed')->exists());

        $this->post('/connexion', ['email' => $user->email, 'password' => 'mot-de-passe-solide'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertTrue(AuditLog::where('action', 'login')->where('user_id', $user->id)->exists());

        $this->post('/deconnexion')->assertRedirect('/connexion');
        $this->assertGuest();
    }

    public function test_home_leads_to_my_account(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/compte');
    }

    public function test_accounts_can_be_created_from_the_command_line(): void
    {
        $this->artisan('compte:create-user', ['email' => 'cli@example.test', '--name' => 'Formateur', '--type' => 'beta'])
            ->expectsQuestion('Mot de passe (10 caractères minimum)', 'un-mot-de-passe-long')
            ->assertSuccessful();

        $user = User::firstWhere('email', 'cli@example.test');
        $this->assertNotNull($user->current_workspace_id);
        $this->assertSame('beta', $user->account_type);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->is_admin);
        $this->post('/connexion', ['email' => 'cli@example.test', 'password' => 'un-mot-de-passe-long'])->assertRedirect('/');
    }

    public function test_command_can_create_an_admin_and_rejects_bad_type(): void
    {
        $this->artisan('compte:create-user', ['email' => 'admin@example.test', '--name' => 'Matthieu', '--admin' => true])
            ->expectsQuestion('Mot de passe (10 caractères minimum)', 'un-mot-de-passe-long')
            ->assertSuccessful();
        $admin = User::firstWhere('email', 'admin@example.test');
        $this->assertTrue($admin->is_admin);
        $this->assertSame('internal', $admin->account_type);

        $this->artisan('compte:create-user', ['email' => 'x@example.test', '--name' => 'X', '--type' => 'vip'])
            ->expectsQuestion('Mot de passe (10 caractères minimum)', 'un-mot-de-passe-long')
            ->assertFailed();
        $this->assertNull(User::firstWhere('email', 'x@example.test'));
    }
}
