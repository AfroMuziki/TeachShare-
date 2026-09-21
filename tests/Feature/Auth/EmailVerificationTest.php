<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.api_key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);
    }

    private function link(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->email),
        ]);
    }

    public function test_verification_prompt_renders_for_unverified_users(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/verify-email')
            ->assertOk()
            ->assertSee('Verify your email');
    }

    public function test_email_can_be_verified_and_welcome_email_is_sent(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->link($user))->assertRedirect('/dashboard?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Http::assertSent(fn (Request $r) => str_contains($r['subject'], 'Welcome'));
    }

    public function test_wrong_hash_does_not_verify(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get($this->link($user, sha1('wrong')))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email/'.$user->id.'/'.sha1($user->email))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_can_be_resent_via_brevo(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertSessionHas('status', 'verification-link-sent');

        Http::assertSent(fn (Request $r) => str_contains($r['subject'], 'Verify') && $r['to'][0]['email'] === $user->email);
    }

    public function test_resend_for_verified_user_sends_nothing(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/email/verification-notification')
            ->assertRedirect('/dashboard');

        Http::assertNothingSent();
    }
}
