<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
<<<<<<< HEAD
        $response = $this->get('/register');
=======
        $response = $this->get(route('register'));
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
<<<<<<< HEAD
        $response = $this->postJson('/api/register', [
            'username' => 'testuser',
            'role' => 'participant',
            'email' => 'test_unique_' . time() . '@example.com',
            'telephone' => '123456789',
=======
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

<<<<<<< HEAD
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
=======
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
>>>>>>> a4d878a35023aeb496e8a22b58cad4a3fa2ae64e
    }
}
