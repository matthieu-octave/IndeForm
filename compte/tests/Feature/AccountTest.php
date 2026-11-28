<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** Mon compte : profil, espace de travail, mot de passe, mot de passe oublié. */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['password' => 'ancien-mot-de-passe']);
    }

    public function test_account_page_shows_profile_workspace_and_modules(): void
    {
        $this->actingAs($this->user)->get('/compte')->assertOk()
            ->assertSee($this->user->email)
            ->assertSee('Mon activité')
            ->assertSee('Form’Pilot')
            ->assertSee('Form’Studio');
    }

    public function test_name_change_keeps_verification(): void
    {
        $this->actingAs($this->user)->put('/compte/profil', ['name' => 'Matthieu', 'email' => $this->user->email])->assertRedirect('/compte');
        $this->assertSame('Matthieu', $this->user->fresh()->name);
        $this->assertNotNull($this->user->fresh()->email_verified_at);
    }

    public function test_email_change_requires_new_verification(): void
    {
        Notification::fake();

        $this->actingAs($this->user)->put('/compte/profil', ['name' => 'X', 'email' => 'nouveau@example.test'])->assertRedirect('/verifier-email');

        $fresh = $this->user->fresh();
        $this->assertSame('nouveau@example.test', $fresh->email);
        $this->assertNull($fresh->email_verified_at);
        Notification::assertSentTo($fresh, VerifyEmail::class);
        $this->assertTrue(AuditLog::where('action', 'email.changed')->exists());
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'pris@example.test']);
        $this->actingAs($this->user)->put('/compte/profil', ['name' => 'X', 'email' => 'pris@example.test'])->assertSessionHasErrors('email');
    }

    public function test_workspace_identity_can_be_updated(): void
    {
        $this->actingAs($this->user)->put('/compte/espace', [
            'name' => 'MOWD', 'legal_name' => 'MO Web Dev', 'siret' => '799 232 715 00026', 'address' => '19 place Jeanne d’Arc',
        ])->assertRedirect('/compte');

        $workspace = $this->user->currentWorkspace()->first();
        $this->assertSame('MOWD', $workspace->name);
        $this->assertSame('799 232 715 00026', $workspace->siret);

        $this->put('/compte/espace', ['name' => 'MOWD', 'siret' => '123'])->assertSessionHasErrors('siret');
    }

    public function test_password_can_be_changed(): void
    {
        $this->actingAs($this->user);
        $this->put('/compte/mot-de-passe', ['current_password' => 'faux', 'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe'])
            ->assertSessionHasErrors('current_password');
        $this->put('/compte/mot-de-passe', ['current_password' => 'ancien-mot-de-passe', 'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe'])
            ->assertRedirect();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $this->user->fresh()->password));
        $this->assertTrue(AuditLog::where('action', 'password.changed')->exists());
    }

    public function test_forgotten_password_flow(): void
    {
        Notification::fake();

        $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@example.test'])->assertSessionHas('status');
        $this->post('/mot-de-passe-oublie', ['email' => $this->user->email])->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPassword::class);

        $token = Password::createToken($this->user);
        $this->post('/reinitialiser-mot-de-passe', [
            'token' => $token, 'email' => $this->user->email,
            'password' => 'mot-de-passe-neuf', 'password_confirmation' => 'mot-de-passe-neuf',
        ])->assertRedirect('/connexion');
        $this->assertTrue(Hash::check('mot-de-passe-neuf', $this->user->fresh()->password));
    }

    public function test_internal_ids_are_never_serialized(): void
    {
        $json = $this->user->fresh()->toArray();
        $this->assertArrayNotHasKey('id', $json);
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('current_workspace_id', $json);
        $this->assertArrayNotHasKey('id', $this->user->currentWorkspace->toArray());
    }
}
