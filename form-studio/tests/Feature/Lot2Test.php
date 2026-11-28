<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\Resource;
use App\Models\SessionStep;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lot 2 : modifier, réordonner, copier une séance, supports en liens. */
class Lot2Test extends TestCase
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

    private function newSession(string $title, int $position): TeachingSession
    {
        return TeachingSession::factory()->for($this->module, 'module')->create(['title' => $title, 'position' => $position]);
    }

    public function test_sessions_can_be_reordered(): void
    {
        $a = $this->newSession('A', 1);
        $b = $this->newSession('B', 2);
        $c = $this->newSession('C', 3);

        $this->post("/sessions/{$c->id}/move", ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['A', 'C', 'B'], $this->module->sessions()->pluck('title')->all());

        $this->post("/sessions/{$a->id}/move", ['direction' => 'up'])->assertRedirect(); // déjà en tête : rien ne bouge
        $this->post("/sessions/{$a->id}/move", ['direction' => 'down'])->assertRedirect();
        $this->assertSame(['C', 'A', 'B'], $this->module->sessions()->pluck('title')->all());

        $this->post("/sessions/{$a->id}/move", ['direction' => 'sideways'])->assertSessionHasErrors('direction');
    }

    public function test_steps_can_be_edited_and_reordered(): void
    {
        $session = $this->newSession('S', 1);
        $one = SessionStep::factory()->for($session, 'session')->create(['title' => 'Apport', 'position' => 1, 'minutes' => 30]);
        $two = SessionStep::factory()->for($session, 'session')->create(['title' => 'TP', 'position' => 2, 'minutes' => 60]);

        $this->put("/steps/{$one->id}", ['title' => 'Apport théorique', 'minutes' => 45, 'instructions' => 'Slides 1 à 12'])->assertRedirect();
        $this->assertSame(['Apport théorique', 45, 'Slides 1 à 12'], [$one->fresh()->title, $one->fresh()->minutes, $one->fresh()->instructions]);

        $this->post("/steps/{$two->id}/move", ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['TP', 'Apport théorique'], $session->steps()->pluck('title')->all());

        $this->put("/steps/{$one->id}", ['title' => '', 'minutes' => -5])->assertSessionHasErrors(['title', 'minutes']);
    }

    public function test_session_title_and_objectives_can_be_edited(): void
    {
        $session = $this->newSession('Ancien titre', 1);
        $this->put("/sessions/{$session->id}", ['title' => 'Nouveau titre', 'objectives' => 'Savoir faire X'])->assertRedirect();
        $this->assertSame('Nouveau titre', $session->fresh()->title);
        $this->assertSame('Savoir faire X', $session->fresh()->objectives);
    }

    public function test_a_single_session_can_be_copied_in_the_module_or_to_another(): void
    {
        $session = $this->newSession('TP Docker', 1);
        $session->update(['notes' => 'privé']);
        SessionStep::factory()->for($session, 'session')->create(['minutes' => 50]);
        Resource::factory()->create(['course_module_id' => $this->module->id, 'teaching_session_id' => $session->id, 'title' => 'Énoncé']);

        $this->post("/sessions/{$session->id}/duplicate")->assertRedirect();
        $copy = $this->module->sessions()->where('title', 'TP Docker (copie)')->firstOrFail();
        $this->assertSame(2, $copy->position);
        $this->assertNull($copy->notes);
        $this->assertSame(50, $copy->totalMinutes());
        $this->assertSame(['Énoncé'], $copy->resources()->pluck('title')->all());

        $other = CourseModule::factory()->create(['workspace_id' => $this->user->workspace_id]);
        $this->post("/sessions/{$session->id}/duplicate", ['course_module_id' => $other->id])->assertRedirect();
        $moved = $other->sessions()->firstOrFail();
        $this->assertSame('TP Docker', $moved->title);
        $this->assertSame($other->id, $moved->resources()->first()->course_module_id);
    }

    public function test_session_cannot_be_copied_into_another_workspace(): void
    {
        $session = $this->newSession('S', 1);
        $foreign = CourseModule::factory()->create(['workspace_id' => User::factory()->create()->workspace_id]);

        $this->post("/sessions/{$session->id}/duplicate", ['course_module_id' => $foreign->id])->assertNotFound();
        $this->assertSame(0, TeachingSession::withoutGlobalScopes()->where('course_module_id', $foreign->id)->count());
    }

    public function test_links_can_be_added_to_module_and_session(): void
    {
        $session = $this->newSession('S', 1);
        $url = 'https://drive.google.com/file/d/abc/view?usp=sharing';

        $this->post("/modules/{$this->module->id}/resources", ['type' => 'support', 'title' => 'Diaporama', 'url' => $url])->assertRedirect();
        $this->post("/modules/{$this->module->id}/resources", [
            'type' => 'exercice', 'title' => 'TP 1', 'url' => $url, 'teaching_session_id' => $session->id,
        ])->assertRedirect();

        $this->assertSame(['Diaporama'], $this->module->moduleResources()->pluck('title')->all());
        $this->assertSame(['TP 1'], $session->resources()->pluck('title')->all());
        $this->get("/modules/{$this->module->id}")->assertSee('Diaporama')->assertSee('TP 1')->assertSee('Google Drive');
    }

    public function test_only_https_links_are_accepted(): void
    {
        foreach (['http://drive.google.com/x', 'javascript:alert(1)', 'ftp://exemple.fr/a', 'drive.google.com/x', 'https://user:pass@exemple.fr', 'https://'] as $bad) {
            $this->post("/modules/{$this->module->id}/resources", ['type' => 'support', 'title' => 'X', 'url' => $bad])
                ->assertSessionHasErrors('url');
        }
        $this->post("/modules/{$this->module->id}/resources", ['type' => 'autre', 'title' => 'X', 'url' => 'https://a.fr'])
            ->assertSessionHasErrors('type');
        $this->assertSame(0, Resource::count());
    }

    public function test_link_cannot_target_a_session_of_another_module(): void
    {
        $other = CourseModule::factory()->create(['workspace_id' => $this->user->workspace_id]);
        $otherSession = TeachingSession::factory()->for($other, 'module')->create();

        $this->post("/modules/{$this->module->id}/resources", [
            'type' => 'support', 'title' => 'X', 'url' => 'https://a.fr', 'teaching_session_id' => $otherSession->id,
        ])->assertSessionHasErrors('teaching_session_id');
    }

    public function test_links_can_be_edited_and_deleted(): void
    {
        $resource = Resource::factory()->create(['course_module_id' => $this->module->id]);

        $this->put("/resources/{$resource->id}", ['type' => 'corrige', 'title' => 'Corrigé TP', 'url' => 'https://docs.google.com/d/1'])->assertRedirect();
        $this->assertSame('corrige', $resource->fresh()->type);

        $this->delete("/resources/{$resource->id}")->assertRedirect();
        $this->assertSame(0, Resource::count());
    }

    public function test_module_copy_also_copies_links_and_maps_sessions(): void
    {
        $session = $this->newSession('S', 1);
        Resource::factory()->create(['course_module_id' => $this->module->id, 'title' => 'Global']);
        Resource::factory()->create(['course_module_id' => $this->module->id, 'teaching_session_id' => $session->id, 'title' => 'Local']);

        $copy = $this->module->duplicate('Copie');

        $this->assertSame(['Global'], $copy->moduleResources()->pluck('title')->all());
        $this->assertSame(['Local'], $copy->sessions()->first()->resources()->pluck('title')->all());
        $this->assertSame(4, Resource::count());
    }

    public function test_links_and_new_actions_are_isolated_between_workspaces(): void
    {
        $session = $this->newSession('S', 1);
        $step = SessionStep::factory()->for($session, 'session')->create();
        $resource = Resource::factory()->create(['course_module_id' => $this->module->id]);

        $this->actingAs(User::factory()->create());
        $this->put("/resources/{$resource->id}", ['type' => 'support', 'title' => 'x', 'url' => 'https://a.fr'])->assertNotFound();
        $this->delete("/resources/{$resource->id}")->assertNotFound();
        $this->post("/modules/{$this->module->id}/resources", ['type' => 'support', 'title' => 'x', 'url' => 'https://a.fr'])->assertNotFound();
        $this->put("/steps/{$step->id}", ['title' => 'x', 'minutes' => 1])->assertNotFound();
        $this->post("/steps/{$step->id}/move", ['direction' => 'up'])->assertNotFound();
        $this->post("/sessions/{$session->id}/move", ['direction' => 'up'])->assertNotFound();
        $this->post("/sessions/{$session->id}/duplicate")->assertNotFound();

        $this->assertSame(1, Resource::withoutGlobalScopes()->count());
        $this->assertSame(1, TeachingSession::withoutGlobalScopes()->count());
    }

    public function test_validation_messages_are_in_french(): void
    {
        $this->post('/modules', ['title' => ''])->assertSessionHasErrors(['title' => 'Le champ intitulé est obligatoire.']);
    }
}
