<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Grace Teacher',
            'email' => 'Grace@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.brevo.api_key' => 'test-key']);
    }

    public function test_registration_screen_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Or sign up with');
    }

    public function test_user_can_register_and_receives_verification_email_via_brevo(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<1@brevo>'], 201)]);

        $this->post('/register', $this->payload())
            ->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();

        $user = User::where('email', 'grace@example.com')->firstOrFail(); // lower-cased
        $this->assertNull($user->email_verified_at);
        $this->assertSame('teacher', $user->fresh()->role);

        Http::assertSentCount(1); // exactly one email, not two
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.brevo.com/v3/smtp/email'
            && $r->hasHeader('api-key', 'test-key')
            && $r['to'][0]['email'] === 'grace@example.com'
            && str_contains($r['htmlContent'], '/verify-email/'));
    }

    public function test_brevo_outage_does_not_break_signup(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response('boom', 500)]);

        $this->post('/register', $this->payload())->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', ['email' => 'grace@example.com']);
        $this->assertAuthenticated();
    }

    public function test_role_cannot_be_mass_assigned_through_the_form(): void
    {
        Http::fake();

        $this->post('/register', $this->payload(['role' => 'admin']));

        $this->assertSame('teacher', User::where('email', 'grace@example.com')->firstOrFail()->fresh()->role);
    }

    public function test_registration_validates_input(): void
    {
        Http::fake();
        User::factory()->create(['email' => 'grace@example.com']);

        $this->post('/register', $this->payload())->assertSessionHasErrors('email'); // duplicate (case-insensitive)
        $this->post('/register', $this->payload(['email' => 'new@example.com', 'password_confirmation' => 'nope']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['email' => 'new@example.com', 'password' => 'short', 'password_confirmation' => 'short']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        $this->post('/register', $this->payload(['name' => '', 'email' => 'new@example.com']))->assertSessionHasErrors('name');
    }
}
