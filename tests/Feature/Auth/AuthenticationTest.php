<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
<<<<<<< HEAD
use Illuminate\Support\Facades\Hash;
=======
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
<<<<<<< HEAD
        $response = $this->get('/login');
=======
        $response = $this->get(route('login'));
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
<<<<<<< HEAD
        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/api/login', [
=======
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), [
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
            'email' => $user->email,
            'password' => 'password',
        ]);

<<<<<<< HEAD
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
=======
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge()
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two-factor authentication is not enabled.');
        }

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
        $this->assertGuest();
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
<<<<<<< HEAD
        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/api/login', [
=======
        $user = User::factory()->create();

        $this->post(route('login.store'), [
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

<<<<<<< HEAD
        $response->assertStatus(401);
=======
        $this->assertGuest();
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

<<<<<<< HEAD
        $response = $this->actingAs($user)->post('/api/logout');

        $this->assertGuest();
=======
        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertTooManyRequests();
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
    }
}
