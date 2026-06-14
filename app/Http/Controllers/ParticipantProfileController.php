<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatutReservation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Evenement;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use App\Concerns\ProfileValidationRules;

class ParticipantProfileController extends Controller
{
    use ProfileValidationRules;

    /**
     * Show the participant settings edit page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // For Participants Stats
        $stats = [
            'total_events' => Reservation::where('user_id', $user->id)
                ->where('statut', StatutReservation::Confirmed)
                ->count(),
            'best_category' => Reservation::where('user_id', $user->id)
                ->where('reservations.statut', StatutReservation::Confirmed)
                ->join('evenements', 'reservations.evenement_id', '=', 'evenements.id')
                ->select('evenements.categorie', DB::raw('count(*) as total'))
                ->groupBy('evenements.categorie')
                ->orderBy('total', 'desc')
                ->first()?->categorie ?? 'None',
            'top_categories' => Reservation::where('user_id', $user->id)
                ->where('reservations.statut', StatutReservation::Confirmed)
                ->join('evenements', 'reservations.evenement_id', '=', 'evenements.id')
                ->select('evenements.categorie', DB::raw('count(*) as total'))
                ->groupBy('evenements.categorie')
                ->orderBy('total', 'desc')
                ->limit(3)
                ->pluck('categorie')
                ->toArray(),
        ];

        return Inertia::render('Participant/Settings', [
            'user' => $user,
            'stats' => $stats,
            'status' => $request->session()->get('status'),
        ]);
    }

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

        // Recent events attended (last 3 confirmed)
        $recentEvents = Reservation::where('user_id', $id)
            ->where('reservations.statut', StatutReservation::Confirmed)
            ->join('evenements', 'reservations.evenement_id', '=', 'evenements.id')
            ->select('evenements.titre', 'evenements.categorie', 'evenements.date_debut', 'evenements.id as event_id')
            ->orderBy('evenements.date_debut', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($e) => [
                'id' => $e->event_id,
                'title' => $e->titre,
                'category' => $e->categorie,
                'date' => $e->date_debut,
            ])
            ->toArray();

        return Inertia::render('Participant/PublicProfile', [
            'participant' => [
                'id' => $participantUser->id,
                'name' => $participantUser->username,
                'email' => $participantUser->email,
                'phone' => $participantUser->telephone,
                'about' => $participantUser->about,
                'member_since' => $participantUser->dateCreation
                    ? date('Y', strtotime($participantUser->dateCreation))
                    : date('Y', strtotime($participantUser->created_at)),
            ],
            'stats' => [
                'total_events' => $totalEvents,
                'interests' => $participantUser->interests ?? [],
                'recent_events' => $recentEvents,
            ],
        ]);
    }

    /**
     * Update the participant's profile.
     */
    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Use 'sometimes' to allow partial updates (like just interests)
        $rules = [
            'username' => array_merge(['sometimes'], $this->usernameRules($user->id)),
            'email' => array_merge(['sometimes'], $this->emailRules($user->id)),
            'telephone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'about' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'interests' => ['sometimes', 'nullable', 'array'],
        ];

        $validated = $request->validate($rules);

        $user->update($validated);

        return back()->with('status', 'profile-updated');
    }
}
