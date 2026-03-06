<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'testuser',
            'role' => 'participant',
            'email' => 'test_unique_' . time() . '@example.com',
            'telephone' => '123456789',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
}
