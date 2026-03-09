<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutReservation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantProfileController extends Controller
{
    /**
     * Show the public profile of a participant.
     */
    public function show(int $id): Response
    {
        $participantUser = User::where('role', Role::Participant)
            ->findOrFail($id);

        // Stats
        $totalEvents = Reservation::where('user_id', $id)
            ->where('statut', StatutReservation::Confirmed)
            ->count();

        return Inertia::render('Participant/PublicProfile', [
            'participant' => [
                'id' => $participantUser->id,
                'name' => $participantUser->username,
                'email' => $participantUser->email,
                'phone' => $participantUser->telephone,
                'about' => $participantUser->about,
            ],
            'stats' => [
                'total_events' => $totalEvents,
                'interests' => $participantUser->interests ?? [],
            ],
        ]);
    }
}
