<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Identifiants publics : UUID v7 générés par le socle, conservés s'ils sont fournis (reprise des comptes Studio). */
class UuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_and_workspaces_get_a_v7_uuid(): void
    {
        $user = User::factory()->create();

        foreach ([$user->uuid, $user->currentWorkspace->uuid] as $uuid) {
            $this->assertTrue(Str::isUuid($uuid));
            $this->assertSame('7', $uuid[14]);
        }
        $this->assertNotSame($user->uuid, $user->currentWorkspace->uuid);
    }

    public function test_a_provided_uuid_is_kept(): void
    {
        $uuid = (string) Str::uuid7();
        $workspace = Workspace::factory()->create(['uuid' => $uuid]);

        $this->assertSame($uuid, $workspace->fresh()->uuid);
    }
}
