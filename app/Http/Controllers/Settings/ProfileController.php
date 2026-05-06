<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Enums\StatutReservation;
use App\Enums\StatutPaiement;
use App\Models\Evenement;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $stats = null;

        if ($user->isOrganisateur() || $user->isRevendeur()) {
            if ($user->isOrganisateur()) {
                $stats = [
                    'type' => 'organizer',
                    'events_count' => Evenement::where('organisateur_id', $user->id)->count(),
                    'revenue' => (float) Paiement::whereHas('reservation.evenement', function ($query) use ($user) {
                        $query->where('organisateur_id', $user->id);
                    })->where('statut', StatutPaiement::Succeeded->value)
                      ->sum('montant') * 0.8,
                ];

                $bestEvents = Evenement::where('organisateur_id', $user->id)
                    ->with(['medias'])
                    ->withCount([
                        'reservations' => function ($query) {
                            $query->confirmed();
                        }
                    ])
                    ->orderBy('reservations_count', 'desc')
                    ->take(5)
                    ->get();
            } else {
                $stats = [
                    'type' => 'revendeur',
                    'events_count' => \App\Models\Commission::where('revendeur_id', $user->revendeur->id)->distinct('evenement_id')->count(),
                    'revenue' => (float) \App\Models\Commission::where('revendeur_id', $user->revendeur->id)
                        ->where('status', \App\Enums\StatutCommission::Approved)
                        ->sum('commission_revendeur'),
                ];
                $bestEvents = [];
            }

            return Inertia::render('settings/Profile', [
                'mustVerifyEmail' => $user instanceof MustVerifyEmail,
                'status' => $request->session()->get('status'),
                'stats' => $stats,
                'bestEvents' => $bestEvents,
            ]);
        }

        // For Participants
        $stats = [
            'type' => 'participant',
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
        ];

        return Inertia::render('Participant/Settings', [
            'user' => $user,
            'stats' => $stats,
            'status' => $request->session()->get('status'),
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
