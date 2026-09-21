<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.api_key' => 'test-key']);
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => 'x'], 201)]);
    }

    private function mockGoogle(string $id, string $email, bool $verified = true, string $name = 'Grace Teacher'): void
    {
        $googleUser = (new SocialiteUser)
            ->map(['id' => $id, 'name' => $name, 'email' => $email, 'avatar' => 'https://example.com/avatar.png'])
            ->setRaw(['email_verified' => $verified]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_redirect_sends_the_user_to_google(): void
    {
        config(['services.google' => [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]]);

        $this->get('/auth/google')->assertRedirectContains('accounts.google.com');
    }

    public function test_new_google_user_is_created_verified_and_signed_in(): void
    {
        $this->mockGoogle('g-123', 'Grace@Example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $user = User::where('email', 'grace@example.com')->firstOrFail();
        $this->assertSame('g-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);
        $this->assertSame('teacher', $user->fresh()->role);
        $this->assertAuthenticatedAs($user);

        Http::assertSent(fn (Request $r) => str_contains($r['subject'], 'Welcome'));
    }

    public function test_existing_verified_account_is_linked_by_email(): void
    {
        $existing = User::factory()->create(['email' => 'grace@example.com']);
        $this->mockGoogle('g-123', 'grace@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertSame(1, User::count());
        $this->assertSame('g-123', $existing->fresh()->google_id);
        $this->assertTrue(Hash::check('password', $existing->fresh()->password)); // password kept
        $this->assertAuthenticatedAs($existing);
        Http::assertNothingSent(); // not a new user, so no welcome email
    }

    public function test_linking_an_unverified_local_account_clears_its_password(): void
    {
        // Pre-hijack protection: an attacker may have registered the victim's address first.
        $existing = User::factory()->unverified()->create(['email' => 'grace@example.com']);
        $this->mockGoogle('g-123', 'grace@example.com');

        $this->get('/auth/google/callback');

        $fresh = $existing->fresh();
        $this->assertNull($fresh->password);
        $this->assertTrue($fresh->hasVerifiedEmail());
        $this->assertSame('g-123', $fresh->google_id);
    }

    public function test_returning_google_user_is_found_by_google_id(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $user->forceFill(['google_id' => 'g-123'])->save();
        $this->mockGoogle('g-123', 'changed@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/dashboard');

        $this->assertSame(1, User::count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_account_without_a_verified_email_is_rejected(): void
    {
        $this->mockGoogle('g-123', 'grace@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect('/sign-in')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_provider_failure_returns_to_sign_in_with_an_error(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('invalid state'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback')->assertRedirect('/sign-in')->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
