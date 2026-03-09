<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class OrganizerProfileController extends Controller
{
    /**
     * Show the public profile of an organizer.
     */
    public function show(int $id): Response
    {
        $organizerUser = User::with('organisateur')
            ->where('role', Role::Organisateur)
            ->findOrFail($id);

        $organisateurProfile = $organizerUser->organisateur;

        // Stats
        $totalEvents = Evenement::where('organisateur_id', $id)->count();
        // Rating is currently not implemented in the DB, so we return a placeholder
        $averageRating = null;

        // Upcoming events
        $upcomingEvents = Evenement::where('organisateur_id', $id)
            ->where('date_debut', '>=', Carbon::now())
            ->orderBy('date_debut', 'asc')
            ->with('medias')
            ->take(3)
            ->get();

        return Inertia::render('Organizer/PublicProfile', [
            'organizer' => [
                'id' => $organizerUser->id,
                'name' => $organisateurProfile?->nom_organisation ?? $organizerUser->username,
                'email' => $organizerUser->email,
                'phone' => $organisateurProfile?->telephone ?? $organizerUser->telephone,
                'about' => $organisateurProfile?->description ?? $organizerUser->about,
                'city' => $organisateurProfile?->adresse ?? 'Not specified',
                'logo' => $organisateurProfile?->logo,
            ],
            'stats' => [
                'total_events' => $totalEvents,
                'average_rating' => $averageRating,
            ],
            'upcoming_events' => $upcomingEvents,
        ]);
    }
}
