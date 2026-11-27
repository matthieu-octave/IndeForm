<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\SessionStep;
use App\Models\TeachingSession;
use App\Support\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_can_be_created_and_found_by_reference_title_or_keyword(): void
    {
        $this->post('/modules', [
            'title' => 'Architecture hexagonale',
            'reference' => 'BC01-UA03',
            'keywords' => 'php, ports',
        ])->assertRedirect();
        CourseModule::factory()->create(['title' => 'Excel avancé']);

        foreach (['hexagonale', 'BC01', 'ports'] as $term) {
            $this->get('/modules?q='.$term)->assertSee('Architecture hexagonale')->assertDontSee('Excel avancé');
        }
    }

    public function test_title_is_required(): void
    {
        $this->post('/modules', ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_steps_are_ordered_and_total_duration_is_computed(): void
    {
        $module = CourseModule::factory()->create();
        $this->post("/modules/{$module->id}/sessions", ['title' => 'Séance 1'])->assertRedirect();
        $session = $module->sessions()->first();

        $this->post("/sessions/{$session->id}/steps", ['title' => 'Apport', 'minutes' => 45])->assertRedirect();
        $this->post("/sessions/{$session->id}/steps", ['title' => 'Exercice', 'minutes' => 90])->assertRedirect();

        $this->assertSame(['Apport', 'Exercice'], $session->steps()->pluck('title')->all());
        $this->assertSame(135, $session->totalMinutes());
        $this->assertSame(135, $module->totalMinutes());
        $this->get("/sessions/{$session->id}/animer")->assertOk()->assertSee('2 h 15');
    }

    public function test_duplicate_is_independent_from_the_original(): void
    {
        $module = CourseModule::factory()->create(['title' => 'Hexagonale', 'reference' => 'BC01-UA03']);
        $session = TeachingSession::factory()->for($module, 'module')->create(['notes' => 'note perso']);
        SessionStep::factory()->for($session, 'session')->create(['title' => 'TP', 'minutes' => 60]);

        $this->post("/modules/{$module->id}/duplicate", ['title' => 'Hexagonale – EADL'])->assertRedirect();

        $copy = CourseModule::where('title', 'Hexagonale – EADL')->firstOrFail();
        $this->assertSame($module->id, $copy->duplicated_from_id);
        $this->assertSame('BC01-UA03', $copy->reference);
        $this->assertSame(60, $copy->totalMinutes());
        $this->assertNull($copy->sessions->first()->notes);

        $copy->sessions->first()->steps->first()->update(['minutes' => 15]);
        $this->assertSame(60, $module->totalMinutes());
    }

    public function test_post_session_notes_are_saved(): void
    {
        $session = TeachingSession::factory()->create();
        $this->put("/sessions/{$session->id}", ['notes' => 'TP +45 min'])->assertRedirect();
        $this->assertSame('TP +45 min', $session->fresh()->notes);
    }

    public function test_duration_format(): void
    {
        $this->assertSame('45 min', Duration::format(45));
        $this->assertSame('2 h', Duration::format(120));
        $this->assertSame('2 h 05', Duration::format(125));
    }
}
