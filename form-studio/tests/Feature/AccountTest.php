<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\SessionStep;
use App\Models\SessionTemplate;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Mon compte : profil, mot de passe, export et suppression des données. */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['password' => 'ancien-mot-de-passe']);
        $this->actingAs($this->user);
    }

    public function test_profile_and_password_can_be_changed(): void
    {
        $this->get('/compte')->assertOk()->assertSee('Supprimer mon compte');

        $this->put('/compte/profil', ['name' => 'Matthieu', 'email' => 'nouveau@example.test'])->assertRedirect();
        $this->assertSame('nouveau@example.test', $this->user->fresh()->email);

        $this->put('/compte/mot-de-passe', ['current_password' => 'faux', 'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe'])
            ->assertSessionHasErrors('current_password');
        $this->put('/compte/mot-de-passe', ['current_password' => 'ancien-mot-de-passe', 'password' => 'nouveau-mot-de-passe', 'password_confirmation' => 'nouveau-mot-de-passe'])
            ->assertRedirect();
        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $this->user->fresh()->password));
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'pris@example.test']);
        $this->put('/compte/profil', ['name' => 'X', 'email' => 'pris@example.test'])->assertSessionHasErrors('email');
    }

    public function test_export_contains_only_my_data(): void
    {
        CourseModule::factory()->create(['workspace_id' => $this->user->workspace_id, 'title' => 'Mon module']);
        CourseModule::factory()->create(['workspace_id' => User::factory()->create()->workspace_id, 'title' => 'Module d’un autre']);
        SessionTemplate::create(['workspace_id' => $this->user->workspace_id, 'name' => 'Mon modèle', 'steps' => []]);

        $json = $this->get('/compte/export')->assertOk()->json();
        $this->assertSame(['Mon module'], array_column(array_column($json['modules'], 'module'), 'title'));
        $this->assertSame(['Mon modèle'], array_column($json['session_templates'], 'name'));
    }

    public function test_account_deletion_removes_all_workspace_data_only(): void
    {
        $module = CourseModule::factory()->create(['workspace_id' => $this->user->workspace_id]);
        $session = TeachingSession::factory()->for($module, 'module')->create();
        SessionStep::factory()->for($session, 'session')->create();
        $other = User::factory()->create();
        CourseModule::factory()->create(['workspace_id' => $other->workspace_id]);

        $this->delete('/compte', ['password' => 'faux', 'confirm' => 1])->assertSessionHasErrors('password');
        $this->delete('/compte', ['password' => 'ancien-mot-de-passe'])->assertSessionHasErrors('confirm');

        $this->delete('/compte', ['password' => 'ancien-mot-de-passe', 'confirm' => 1])->assertRedirect('/connexion');
        $this->assertGuest();
        $this->assertNull(User::find($this->user->id));
        $this->assertNull(Workspace::find($this->user->workspace_id));
        $this->assertSame(1, CourseModule::withoutGlobalScopes()->count());
        $this->assertSame(0, TeachingSession::withoutGlobalScopes()->count());
        $this->assertSame(0, SessionStep::withoutGlobalScopes()->count());
        $this->assertGreaterThan(0, SessionTemplate::withoutGlobalScopes()->whereNull('workspace_id')->count());
    }
}
