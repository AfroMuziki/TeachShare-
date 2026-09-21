<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const STATUS = 'If an account exists for that email, we have sent a password reset link.';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.api_key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);
    }

    private function reset(User $user, string $token, string $password = 'newpassword1')
    {
        return $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Send reset link');
    }

    public function test_reset_link_is_sent_through_brevo(): void
    {
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status', self::STATUS);

        Http::assertSent(fn (Request $r) => $r['to'][0]['email'] === $user->email
            && str_contains($r['htmlContent'], '/reset-password/'));
    }

    public function test_unknown_email_gets_the_same_response_and_no_email(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status', self::STATUS);

        Http::assertNothingSent();
    }

    public function test_forgot_password_is_rate_limited_per_email_and_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHas('status');
        }

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])->assertSessionHasErrors('email');
    }

    public function test_reset_password_screen_renders(): void
    {
        $this->get('/reset-password/some-token?email=a@example.com')->assertOk()->assertSee('Choose a new password');
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->reset($user, $token)->assertRedirect('/sign-in')->assertSessionHas('status');

        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
    }

    public function test_token_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->reset($user, $token);
        $this->reset($user, $token, 'anotherpass2')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('newpassword1', $user->fresh()->password));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->reset($user, 'not-a-real-token')->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_signs_the_account_out_of_other_sessions(): void
    {
        $user = User::factory()->create();
        DB::table('sessions')->insert(['id' => 'stale-session', 'user_id' => $user->id, 'payload' => 'x', 'last_activity' => time()]);

        $this->reset($user, Password::createToken($user));

        $this->assertDatabaseMissing('sessions', ['id' => 'stale-session']);
    }

    public function test_reset_submissions_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->reset($user, 'guess-'.$i)->assertSessionHasErrors('email');
        }

        $response = $this->reset($user, 'guess-6');
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many', session('errors')->first('email'));
    }
}
