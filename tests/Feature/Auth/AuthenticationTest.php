<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_users_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/logout');

        $this->assertGuest();
    }
}
