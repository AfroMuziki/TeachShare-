<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use App\Services\LoginThrottleService;

class BruteForceProtectionTest extends TestCase
{
    use RefreshDatabase;

    private const IP = '203.0.113.10';

    private LoginThrottleService $throttle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->throttle = app(LoginThrottleService::class);
    }

    private function attempt(string $email, string $password = 'wrong-password', string $ip = self::IP, array $extra = [])
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/sign-in')
            ->post('/sign-in', array_merge(['email' => $email, 'password' => $password], $extra));
    }

    /** Simulate n failed attempts through the service (bypasses the HTTP per-minute ceiling). */
    private function fail(string $email, string $ip, int $times = 1): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->throttle->hit($email, $ip);
            $this->throttle->recordFailure($email, $ip);
        }
    }

    // ---- The three counters -------------------------------------------------

    public function test_sixth_attempt_within_a_minute_is_blocked_even_with_the_right_password(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt($user->email)->assertSessionHasErrors(['email' => 'Invalid credentials.']);
        }

        $this->assertTrue($this->throttle->isBlocked($user->email, self::IP));

        $this->attempt($user->email, 'password')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNotSame('Invalid credentials.', session('errors')->first('email'));
    }

    public function test_different_ips_do_not_share_the_email_plus_ip_counter(): void
    {
        $email = 'teacher@example.com';

        for ($i = 0; $i < 5; $i++) {
            $this->throttle->hit($email, '198.51.100.1');
        }

        $this->assertTrue($this->throttle->isBlocked($email, '198.51.100.1'));
        $this->assertFalse($this->throttle->isBlocked($email, '198.51.100.2'));
    }

    public function test_same_email_from_rotating_ips_still_hits_the_email_counter(): void
    {
        $email = 'teacher@example.com';

        for ($i = 1; $i <= 10; $i++) {
            $this->throttle->hit($email, "198.51.100.$i");
        }

        // A brand-new IP with a clean IP counter is still blocked for this email.
        $this->assertTrue($this->throttle->isBlocked($email, '198.51.100.200'));
        $this->assertFalse($this->throttle->isBlocked('someone-else@example.com', '198.51.100.200'));
    }

    public function test_one_ip_spraying_many_emails_is_blocked_by_the_ip_counter(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->throttle->hit("user{$i}@example.com", '198.51.100.7');
        }

        $this->assertTrue($this->throttle->isBlocked('fresh-victim@example.com', '198.51.100.7'));
        $this->assertFalse($this->throttle->isBlocked('fresh-victim@example.com', '198.51.100.8'));
    }

    public function test_route_level_ceiling_returns_429_after_ten_posts_a_minute_from_one_ip(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->attempt("user{$i}@example.com");
        }

        $this->attempt('user11@example.com')->assertStatus(429);
    }

    // ---- Recovery and reset --------------------------------------------------

    public function test_block_and_lockout_lift_after_waiting(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->attempt($user->email);
        }

        $this->travel(61)->seconds(); // email+IP counter has decayed, lockout has not
        $this->attempt($user->email, 'password')->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->travel(15)->minutes(); // 15 min lockout has expired
        $this->attempt($user->email, 'password');
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_resets_user_counters_but_not_the_ip_counter(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($user->email);
        }

        $keys = $this->throttle->keys($user->email, self::IP);
        $this->assertSame(3, RateLimiter::attempts($keys['ip_email']));

        $this->attempt($user->email, 'password');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, RateLimiter::attempts($keys['ip_email']));
        $this->assertSame(0, RateLimiter::attempts($keys['email']));
        $this->assertSame(0, $this->throttle->failureCount($user->email));
        $this->assertSame(3, RateLimiter::attempts($keys['ip'])); // IP history is kept
    }

    // ---- Escalating lockout --------------------------------------------------

    public function test_lockout_escalates_15_minutes_then_1_hour_then_24_hours(): void
    {
        $email = 'teacher@example.com';

        // 5 failures -> 15 minutes
        $this->fail($email, self::IP, 5);
        $this->assertTrue($this->throttle->isLockedOut($email));
        $this->travel(14)->minutes();
        $this->assertTrue($this->throttle->isLockedOut($email));
        $this->travel(2)->minutes();
        $this->assertFalse($this->throttle->isLockedOut($email));

        // 10 failures -> 1 hour
        $this->fail($email, self::IP, 5);
        $this->assertTrue($this->throttle->isLockedOut($email));
        $this->travel(59)->minutes();
        $this->assertTrue($this->throttle->isLockedOut($email));
        $this->travel(2)->minutes();
        $this->assertFalse($this->throttle->isLockedOut($email));

        // 20 failures -> 24 hours
        $this->fail($email, self::IP, 10);
        $this->assertTrue($this->throttle->isLockedOut($email));
        $this->travel(23)->hours();
        $this->assertTrue($this->throttle->isLockedOut($email));
        $this->travel(2)->hours();
        $this->assertFalse($this->throttle->isLockedOut($email));
    }

    public function test_lockout_is_keyed_on_the_email_string_so_it_cannot_reveal_accounts(): void
    {
        // No user with this email exists, yet it locks exactly like a real one.
        for ($i = 0; $i < 5; $i++) {
            $this->attempt('ghost@example.com');
        }

        $this->attempt('ghost@example.com')->assertSessionHasErrors('email');
        $this->assertTrue($this->throttle->isLockedOut('ghost@example.com'));
    }

    // ---- Logging and alerts --------------------------------------------------

    public function test_failed_attempts_are_logged_with_email_ip_and_user_agent(): void
    {
        Log::spy();

        $this->withHeaders(['User-Agent' => 'TestAgent/1.0'])
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post('/sign-in', ['email' => 'who@example.com', 'password' => 'nope']);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'who@example.com',
            'ip_address' => '203.0.113.7',
            'user_agent' => 'TestAgent/1.0',
            'successful' => false,
        ]);

        Log::shouldHaveReceived('warning')->withArgs(
            fn ($message, $context = []) => $message === 'Failed login'
                && $context['email'] === 'who@example.com'
                && $context['ip'] === '203.0.113.7'
                && $context['user_agent'] === 'TestAgent/1.0'
        );
    }

    public function test_successful_logins_are_recorded_for_audit(): void
    {
        $user = User::factory()->create();

        $this->attempt($user->email, 'password');

        $this->assertDatabaseHas('login_attempts', ['email' => $user->email, 'successful' => true]);
    }

    public function test_admin_is_alerted_once_when_an_ip_reaches_twenty_failures(): void
    {
        config(['services.brevo.api_key' => 'test-key', 'services.teachshare.admin_email' => 'admin@teachshare.app']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);

        for ($i = 1; $i <= 19; $i++) {
            $this->fail("user{$i}@example.com", '198.51.100.9');
        }
        Http::assertNothingSent();

        $this->fail('user20@example.com', '198.51.100.9');
        $this->fail('user21@example.com', '198.51.100.9'); // must not alert again

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['to'][0]['email'] === 'admin@teachshare.app'
            && str_contains($r['subject'], 'Suspicious')
            && str_contains($r['htmlContent'], '198.51.100.9'));
    }

    // ---- CAPTCHA -------------------------------------------------------------

    public function test_captcha_is_required_after_three_failures_when_turnstile_is_configured(): void
    {
        config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $this->attempt($user->email)->assertSessionHasErrors(['email' => 'Invalid credentials.']);
        }

        // 4th try with the RIGHT password but no captcha token is refused.
        $this->attempt($user->email, 'password')->assertSessionHasErrors('captcha');
        $this->assertGuest();

        // The sign-in page now renders the widget for that email.
        $this->withSession(['_old_input' => ['email' => $user->email]])
            ->get('/sign-in')
            ->assertSee('cf-turnstile', false);

        // With a valid token the login goes through.
        $this->attempt($user->email, 'password', self::IP, ['cf-turnstile-response' => 'token']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_captcha_never_appears_when_turnstile_is_not_configured(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->attempt($user->email);
        }

        $this->assertFalse($this->throttle->requiresCaptcha($user->email, self::IP));
    }
}
