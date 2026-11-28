<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\SessionStep;
use App\Models\SessionTemplate;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lot 3 : modèles de séance fournis et personnels. */
class TemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CourseModule $module;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->module = CourseModule::factory()->create(['workspace_id' => $this->user->workspace_id]);
    }

    public function test_provided_templates_exist_for_every_account(): void
    {
        $names = SessionTemplate::pluck('name')->all();
        $this->assertContains('Cours de 2 h', $names);
        $this->assertContains('Évaluation (2 h)', $names);
        $this->assertSame(120, SessionTemplate::firstWhere('name', 'Cours de 2 h')->totalMinutes());
        $this->assertSame(420, SessionTemplate::firstWhere('name', 'Journée complète (7 h)')->totalMinutes());

        $this->get('/modeles')->assertOk()->assertSee('TP guidé (3 h)');
    }

    public function test_session_can_be_created_from_a_template_and_is_independent(): void
    {
        $template = SessionTemplate::firstWhere('name', 'Cours de 2 h');

        $this->post("/modules/{$this->module->id}/sessions", ['template_id' => $template->id, 'title' => 'Ports et adaptateurs'])->assertRedirect();

        $session = $this->module->sessions()->firstOrFail();
        $this->assertSame('Ports et adaptateurs', $session->title);
        $this->assertSame(120, $session->totalMinutes());
        $this->assertSame('Accueil et objectifs de la séance', $session->steps()->first()->title);

        $session->steps()->first()->update(['minutes' => 30]);
        $this->assertSame(120, $template->fresh()->totalMinutes());
    }

    public function test_template_title_defaults_to_its_name_and_empty_session_needs_a_title(): void
    {
        $template = SessionTemplate::firstWhere('name', 'TP guidé (3 h)');
        $this->post("/modules/{$this->module->id}/sessions", ['template_id' => $template->id])->assertRedirect();
        $this->assertSame('TP guidé (3 h)', $this->module->sessions()->first()->title);

        $this->post("/modules/{$this->module->id}/sessions", ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_session_can_be_saved_as_a_personal_template(): void
    {
        $session = TeachingSession::factory()->for($this->module, 'module')->create();
        SessionStep::factory()->for($session, 'session')->create(['title' => 'Kata', 'minutes' => 40, 'position' => 1]);
        SessionStep::factory()->for($session, 'session')->create(['title' => 'Revue', 'minutes' => 20, 'position' => 2]);

        $this->post("/sessions/{$session->id}/template", ['name' => 'Mon dojo'])->assertRedirect();

        $template = SessionTemplate::firstWhere('name', 'Mon dojo');
        $this->assertSame($this->user->workspace_id, $template->workspace_id);
        $this->assertSame(['Kata', 'Revue'], array_column($template->steps, 'title'));
        $this->assertSame(60, $template->totalMinutes());
    }

    public function test_personal_templates_are_private_and_provided_ones_read_only(): void
    {
        $mine = SessionTemplate::create(['workspace_id' => $this->user->workspace_id, 'name' => 'Secret', 'steps' => [['title' => 'A', 'minutes' => 5]]]);
        $provided = SessionTemplate::whereNull('workspace_id')->first();

        $this->delete("/modeles/{$provided->id}")->assertForbidden();
        $this->put("/modeles/{$provided->id}", ['name' => 'x'])->assertForbidden();

        $this->put("/modeles/{$mine->id}", ['name' => 'Renommé'])->assertRedirect();
        $this->assertSame('Renommé', $mine->fresh()->name);

        $bob = User::factory()->create();
        $bobModule = CourseModule::factory()->create(['workspace_id' => $bob->workspace_id]);
        $this->actingAs($bob);
        $this->get('/modeles')->assertDontSee('Renommé');
        $this->post("/modules/{$bobModule->id}/sessions", ['template_id' => $mine->id])->assertNotFound();
        $this->delete("/modeles/{$mine->id}")->assertNotFound();
        $this->assertNotNull(SessionTemplate::withoutGlobalScopes()->find($mine->id));

        $this->actingAs($this->user)->delete("/modeles/{$mine->id}")->assertRedirect();
        $this->assertNull(SessionTemplate::withoutGlobalScopes()->find($mine->id));
    }

    public function test_cannot_save_a_foreign_session_as_template(): void
    {
        $session = TeachingSession::factory()->for($this->module, 'module')->create();
        $this->actingAs(User::factory()->create())->post("/sessions/{$session->id}/template", ['name' => 'Vol'])->assertNotFound();
        $this->assertNull(SessionTemplate::withoutGlobalScopes()->firstWhere('name', 'Vol'));
    }
}
