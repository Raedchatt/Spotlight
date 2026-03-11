<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Evenement;
use App\Models\Media;
use App\Models\User;
use App\Enums\CategorieEvenement;
use App\Enums\StatutEvenement;
use App\Enums\TypeMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        // Disable foreign key checks to truncate tables
        Schema::disableForeignKeyConstraints();
        DB::table('evenements')->truncate();
        DB::table('media')->truncate();
        Schema::enableForeignKeyConstraints();

        $organisers = User::where('role', 'organisateur')->get();

        if ($organisers->isEmpty()) {
            $this->command->error('No organisers found. Please seed users first.');
            return;
        }

        // We will use Picsum with a seed to get unique, reliable images for each event
        $videoUrls = [
            'https://res.cloudinary.com/drz1zvzev/video/upload/v1773193116/events/media/szq1rsnb57rkss6jfhud.mp4',
        ];

        $categories = CategorieEvenement::cases();
        
        for ($i = 1; $i <= 20; $i++) {
            $organiser = $organisers->random();
            $category = $categories[array_rand($categories)];
            
            // Use Picsum with a unique seed for each event to ensure reliability and variety
            $imageUrl = "https://picsum.photos/seed/event_{$i}/1200/800";
            $videoUrl = $videoUrls[array_rand($videoUrls)];

            $event = Evenement::create([
                'organisateur_id' => $organiser->id,
                'titre' => "Seeded Event #$i: " . ucfirst($category->value) . " Gala",
                'description' => "This is a detailed description for seeded event #$i. It features amazing performances, networking opportunities, and a great atmosphere for all attendees.",
                'date_debut' => Carbon::now()->addDays($i)->setHour(18)->setMinute(0),
                'date_fin' => Carbon::now()->addDays($i)->setHour(22)->setMinute(0),
                'lieu' => "Grand Hall Spotlight, City Center " . ($i % 5 + 1),
                'prix_spectateur' => rand(20, 150),
                'capacite_spectateur' => rand(50, 500),
                'statut' => StatutEvenement::Ouvert,
                'categorie' => $category,
                'is_tournoi' => false,
                'poster_url' => $imageUrl,
                'poster_public_id' => 'seeded_poster_' . $i,
                'video_url' => $videoUrl,
                'video_public_id' => 'seeded_video_' . $i,
            ]);

            // Add to polymorphic media table as well
            $event->medias()->create([
                'url' => $imageUrl,
                'type' => TypeMedia::Image,
                'date_upload' => Carbon::now(),
            ]);

            $event->medias()->create([
                'url' => $videoUrl,
                'type' => TypeMedia::Video,
                'date_upload' => Carbon::now(),
            ]);
        }

        $this->command->info('20 events seeded successfully with media.');
    }
}
