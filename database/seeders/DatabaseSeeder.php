<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test participant
        User::factory()->create([
            'username' => 'TestParticipant',
            'email' => 'test@example.com',
            'role' => 'participant',
        ]);

        // Create a test organiser
        User::factory()->create([
            'username' => 'TestOrganiser',
            'email' => 'organiser@example.com',
            'role' => 'organisateur',
        ]);

        $this->call(EventSeeder::class);
    }
}
