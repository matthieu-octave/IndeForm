<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_users_must_confirm_their_address(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/compte')->assertRedirect('/verifier-email');
        $this->get('/verifier-email')->assertOk()->assertSee($user->email);
    }

    public function test_signed_link_verifies_the_address(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect('/');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/compte')->assertOk();
    }

    public function test_wrong_or_unsigned_links_are_refused(): void
    {
        $user = User::factory()->unverified()->create();
        $bad = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('autre@example.test')]);

        $this->actingAs($user)->get($bad)->assertForbidden();
        $this->get('/verifier-email/'.$user->id.'/'.sha1($user->email))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_link_can_be_sent_again(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/verifier-email/renvoyer')->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
