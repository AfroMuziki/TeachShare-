<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_screen_renders(): void
    {
        $this->get('/sign-in')
            ->assertOk()
            ->assertSee('Sign in to your account')
            ->assertSee('Remember me')
            ->assertSee('Forgot Password?')
            ->assertSee('Or sign in with')
            ->assertSee('New user?');
    }

    public function test_root_redirects_by_auth_state(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_verified_user_can_sign_in(): void
    {
        $user = User::factory()->create();

        $this->post('/sign-in', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_email_is_case_insensitive_on_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'grace@example.com']);

        $this->post('/sign-in', ['email' => 'GRACE@Example.com', 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_unknown_email_return_the_same_generic_error(): void
    {
        $user = User::factory()->create();

        $known = $this->from('/sign-in')->post('/sign-in', ['email' => $user->email, 'password' => 'wrong-password']);
        $unknown = $this->from('/sign-in')->post('/sign-in', ['email' => 'nobody@example.com', 'password' => 'wrong-password']);

        $known->assertRedirect('/sign-in')->assertSessionHasErrors(['email' => 'Invalid credentials.']);
        $unknown->assertRedirect('/sign-in')->assertSessionHasErrors(['email' => 'Invalid credentials.']);
        $this->assertGuest();
    }

    public function test_google_only_account_cannot_sign_in_with_a_password(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['password' => null])->save();

        $this->post('/sign-in', ['email' => $user->email, 'password' => ''])->assertSessionHasErrors('password');
        $this->post('/sign-in', ['email' => $user->email, 'password' => 'anything'])
            ->assertSessionHasErrors(['email' => 'Invalid credentials.']);
        $this->assertGuest();
    }

    public function test_remember_me_sets_the_long_lived_cookie(): void
    {
        $user = User::factory()->create();
        $recaller = Auth::guard('web')->getRecallerName();

        $this->post('/sign-in', ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
            ->assertCookie($recaller);
    }

    public function test_without_remember_me_no_long_lived_cookie_is_set(): void
    {
        $user = User::factory()->create();
        $recaller = Auth::guard('web')->getRecallerName();

        $this->post('/sign-in', ['email' => $user->email, 'password' => 'password'])
            ->assertCookieMissing($recaller);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_requires_a_verified_email(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_dashboard_greets_the_user(): void
    {
        $user = User::factory()->create(['name' => 'Grace Teacher']);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Welcome, Grace Teacher');
    }

    public function test_signed_in_users_are_redirected_away_from_guest_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/sign-in')->assertRedirect(route('dashboard'));
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_cannot_log_out(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }
}
