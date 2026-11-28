<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\Resource;
use App\Models\SessionStep;
use App\Models\TeachingSession;
use App\Models\User;
use App\Support\Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Lot 4 : vue pour animer (heures, chronomètre), prévu / réel, export / import JSON. */
class Lot4Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private CourseModule $module;

    private TeachingSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->module = CourseModule::factory()->create(['workspace_id' => $this->user->workspace_id, 'title' => 'Hexagonale', 'reference' => 'BC01-UA03']);
        $this->session = TeachingSession::factory()->for($this->module, 'module')->create(['title' => 'Ports']);
        SessionStep::factory()->for($this->session, 'session')->create(['title' => 'Apport', 'minutes' => 45, 'position' => 1]);
        SessionStep::factory()->for($this->session, 'session')->create(['title' => 'TP', 'minutes' => 90, 'position' => 2]);
    }

    public function test_present_view_shows_planned_clock_times(): void
    {
        $this->get("/sessions/{$this->session->id}/animer?debut=09:00")
            ->assertOk()->assertSee('09:00')->assertSee('09:45')->assertSee('de 09:00 à 11:15')->assertSee('Démarrer');

        $this->get("/sessions/{$this->session->id}/animer?debut=25:99")->assertOk()->assertDontSee('25:99');
    }

    public function test_clock_and_delta_helpers(): void
    {
        $this->assertSame('11:15', Duration::clock('09:00', 135));
        $this->assertSame('00:30', Duration::clock('23:45', 45));
        $this->assertSame('+15 min', Duration::delta(15));
        $this->assertSame('−1 h 05', Duration::delta(-65));
        $this->assertSame('à l’heure', Duration::delta(0));
    }

    public function test_actual_durations_are_saved_and_compared(): void
    {
        [$apport, $tp] = $this->session->steps()->get()->all();

        $this->put("/sessions/{$this->session->id}/reel", ['actual' => [$apport->id => 50, $tp->id => 110]])->assertRedirect();
        $this->assertSame(50, $apport->fresh()->actual_minutes);
        $this->assertSame(160, $this->session->fresh()->actualTotalMinutes());

        $this->get("/modules/{$this->module->id}")->assertSee('réel 2 h 40 (+25 min)');

        $this->put("/sessions/{$this->session->id}/reel", ['reset' => 1])->assertRedirect();
        $this->assertNull($this->session->fresh()->actualTotalMinutes());

        $this->put("/sessions/{$this->session->id}/reel", ['actual' => [$apport->id => -3]])->assertSessionHasErrors('actual.'.$apport->id);
    }

    public function test_actual_durations_are_not_copied_with_the_session(): void
    {
        $this->session->steps()->update(['actual_minutes' => 99]);
        $copy = $this->session->fresh()->duplicateInto($this->module);
        $this->assertNull($copy->steps()->first()->actual_minutes);
    }

    public function test_module_export_and_import_round_trip(): void
    {
        Resource::factory()->create(['course_module_id' => $this->module->id, 'title' => 'Diaporama']);
        Resource::factory()->create(['course_module_id' => $this->module->id, 'teaching_session_id' => $this->session->id, 'type' => 'exercice', 'title' => 'TP 1']);

        $response = $this->get("/modules/{$this->module->id}/export")->assertOk();
        $this->assertStringContainsString('bc01-ua03-hexagonale.formstudio.json', $response->headers->get('Content-Disposition'));
        $json = $response->json();
        $this->assertSame('formstudio-module', $json['format']);
        $this->assertSame(['Apport', 'TP'], array_column($json['module']['sessions'][0]['steps'], 'title'));

        // Un autre formateur importe le fichier dans son propre espace.
        $bob = User::factory()->create();
        $this->actingAs($bob);
        $file = UploadedFile::fake()->createWithContent('export.json', json_encode($json));
        $this->post('/modules/import', ['file' => $file])->assertRedirect();

        $imported = CourseModule::firstOrFail();
        $this->assertSame($bob->workspace_id, $imported->workspace_id);
        $this->assertSame(135, $imported->totalMinutes());
        $this->assertSame(['Diaporama'], $imported->moduleResources()->pluck('title')->all());
        $this->assertSame(['TP 1'], $imported->sessions()->first()->resources()->pluck('title')->all());
    }

    public function test_import_rejects_invalid_files(): void
    {
        $bad = [
            'pas du json',
            json_encode(['format' => 'autre', 'version' => 1, 'module' => ['title' => 'X']]),
            json_encode(['format' => 'formstudio-module', 'version' => 1, 'module' => ['title' => 'X', 'resources' => [['type' => 'support', 'title' => 'x', 'url' => 'javascript:alert(1)']]]]),
        ];
        foreach ($bad as $content) {
            $this->post('/modules/import', ['file' => UploadedFile::fake()->createWithContent('x.json', $content)])->assertSessionHasErrors();
        }
        $this->assertSame(1, CourseModule::count());
    }

    public function test_foreign_users_cannot_export_or_record_actuals(): void
    {
        $step = $this->session->steps()->first();
        $this->actingAs(User::factory()->create());
        $this->get("/modules/{$this->module->id}/export")->assertNotFound();
        $this->put("/sessions/{$this->session->id}/reel", ['actual' => [$step->id => 5]])->assertNotFound();
        $this->assertNull(SessionStep::withoutGlobalScopes()->find($step->id)->actual_minutes);
    }
}
