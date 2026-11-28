<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\SessionStep;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Un compte ne doit jamais lire, modifier, supprimer ni dupliquer les données d'un autre. */
class WorkspaceIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    private CourseModule $module;

    private TeachingSession $session;

    private SessionStep $step;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();

        $this->module = CourseModule::factory()->create(['title' => 'Secret Alice', 'workspace_id' => $this->alice->workspace_id]);
        $this->session = TeachingSession::factory()->for($this->module, 'module')->create();
        $this->step = SessionStep::factory()->for($this->session, 'session')->create();

        $this->actingAs($this->bob);
    }

    public function test_list_and_search_do_not_leak(): void
    {
        $this->get('/modules')->assertOk()->assertDontSee('Secret Alice');
        $this->get('/modules?q=Secret')->assertOk()->assertDontSee('Secret Alice');
    }

    public function test_foreign_module_is_not_found_for_every_action(): void
    {
        $id = $this->module->id;
        $this->get("/modules/{$id}")->assertNotFound();
        $this->get("/modules/{$id}/edit")->assertNotFound();
        $this->put("/modules/{$id}", ['title' => 'Piraté'])->assertNotFound();
        $this->delete("/modules/{$id}")->assertNotFound();
        $this->post("/modules/{$id}/duplicate")->assertNotFound();
        $this->post("/modules/{$id}/sessions", ['title' => 'Intrus'])->assertNotFound();

        $this->assertSame('Secret Alice', $this->module->fresh()->title);
        $this->assertSame(1, CourseModule::withoutGlobalScopes()->count());
    }

    public function test_foreign_sessions_and_steps_are_not_reachable(): void
    {
        $this->get("/sessions/{$this->session->id}/animer")->assertNotFound();
        $this->put("/sessions/{$this->session->id}", ['notes' => 'x'])->assertNotFound();
        $this->delete("/sessions/{$this->session->id}")->assertNotFound();
        $this->post("/sessions/{$this->session->id}/steps", ['title' => 'x', 'minutes' => 5])->assertNotFound();
        $this->delete("/steps/{$this->step->id}")->assertNotFound();

        $this->assertNull($this->session->fresh()->notes);
        $this->assertSame(1, SessionStep::withoutGlobalScopes()->count());
    }

    public function test_new_data_is_attached_to_the_creators_workspace(): void
    {
        $this->post('/modules', ['title' => 'Module de Bob'])->assertRedirect();

        $mine = CourseModule::firstWhere('title', 'Module de Bob');
        $this->assertSame($this->bob->workspace_id, $mine->workspace_id);

        $this->actingAs($this->alice)->get("/modules/{$mine->id}")->assertNotFound();
    }

    public function test_duplicate_stays_in_the_workspace_of_the_owner(): void
    {
        $this->actingAs($this->alice)->post("/modules/{$this->module->id}/duplicate")->assertRedirect();

        $copy = CourseModule::withoutGlobalScopes()->where('duplicated_from_id', $this->module->id)->firstOrFail();
        $this->assertSame($this->alice->workspace_id, $copy->workspace_id);
    }

    public function test_without_a_session_nothing_is_visible(): void
    {
        auth()->logout();
        $this->assertSame(0, CourseModule::count());
        $this->assertSame(0, TeachingSession::count());
        $this->assertSame(0, SessionStep::count());
    }
}
