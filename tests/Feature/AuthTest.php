<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_welcome_page_and_account_forms_are_available_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>The World of Magnar</title>', false)
            ->assertSee('id="app"', false)
            ->assertSee('data-authenticated="false"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create an account · The World of Magnar')
            ->assertSee('Create your account')
            ->assertSee('password_confirmation');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in · The World of Magnar')
            ->assertSee('Welcome back')
            ->assertSee('Remember me');
    }

    public function test_a_visitor_can_create_an_account_and_is_signed_in(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ari Vale',
            'email' => 'ari@example.com',
            'password' => 'a-long-password',
            'password_confirmation' => 'a-long-password',
        ]);

        $user = User::where('email', 'ari@example.com')->firstOrFail();

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('a-long-password', $user->password));
    }

    public function test_registration_requires_a_unique_email_and_confirmed_password(): void
    {
        User::factory()->create(['email' => 'ari@example.com']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Ari Vale',
                'email' => 'ari@example.com',
                'password' => 'a-long-password',
                'password_confirmation' => 'a-different-password',
            ])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_a_registered_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create([
            'email' => 'ari@example.com',
            'password' => 'a-long-password',
        ]);

        $this->post(route('login.store'), [
            'email' => 'ari@example.com',
            'password' => 'a-long-password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_sign_a_visitor_in(): void
    {
        User::factory()->create([
            'email' => 'ari@example.com',
            'password' => 'a-long-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'ari@example.com',
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
