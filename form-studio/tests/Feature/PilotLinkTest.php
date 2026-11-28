<?php

namespace Tests\Feature;

use App\Models\CourseModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lot 6 (amorce) : ouvrir un module par sa référence, depuis un lien Form'Pilot. */
class PilotLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_link_opens_the_module_of_my_workspace(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $module = CourseModule::factory()->create(['workspace_id' => $user->workspace_id, 'reference' => 'BC01-UA03']);
        CourseModule::factory()->create(['workspace_id' => User::factory()->create()->workspace_id, 'reference' => 'BC02-UA01']);

        $this->get('/modules/ref/BC01-UA03')->assertRedirect("/modules/{$module->id}");
        $this->get('/modules/ref/BC02-UA01')->assertRedirect('/modules?q=BC02-UA01'); // module d'un autre compte : introuvable

        CourseModule::factory()->create(['workspace_id' => $user->workspace_id, 'reference' => 'BC01-UA03', 'title' => 'Copie EADL']);
        $this->get('/modules/ref/BC01-UA03')->assertRedirect('/modules?q=BC01-UA03');
    }

    public function test_guest_is_sent_to_login_then_back(): void
    {
        $this->get('/modules/ref/BC01-UA03')->assertRedirect('/connexion');
    }
}
