<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Models\Paiement;
use App\Enums\StatutPaiement;
use App\Enums\StatutReservation;
use App\Enums\Role;
use App\Enums\StatutEvenement; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->role === Role::Revendeur) {
            return app(\App\Http\Controllers\Affiliate\AffiliateDashboardController::class)->index(request());
        }

        // Default for participants/others
        return Inertia::render('Dashboard', [
            'dashboardData' => null
        ]);
    }

    protected function adminDashboard()
    {
        // 1. Basic Stats
        $totalUsers = User::count();
        $totalOrganizers = User::where('role', '=', Role::Organisateur)->count();
        $totalParticipants = User::where('role', '=', Role::Participant)->where('statut', '=', 'actif')->count();
        $totalEvents = Evenement::count();
        $totalReservations = Reservation::count();

        // 2. Financial Stats
        $totalRevenue = Paiement::where('statut', '=', StatutPaiement::Succeeded)->sum('montant');
        $siteCommission = $totalRevenue * 0.10;

        // 3. Events Stats list
        $recentEvents = Evenement::with(['organisateur:id,username'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($event) {
                // Number of reservations for this event
                $resCount = Reservation::where('evenement_id', $event->id)->count();

                // Revenue calculation
                $eventRevenue = Paiement::whereHas('reservation', function($q) use ($event) {
                    $q->where('evenement_id', '=', $event->id);
                })->where('statut', '=', StatutPaiement::Succeeded)->sum('montant');

                return [
                    'id' => $event->id,
                    'titre' => $event->titre,
                    'organisateur' => $event->organisateur->username,
                    'reservations_count' => $resCount,
                    'revenue' => round($eventRevenue, 2),
                    'commission' => round($eventRevenue * 0.10, 2),
                    'statut' => $event->statut instanceof StatutEvenement ? $event->statut->value : $event->statut,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_organizers' => $totalOrganizers,
                'total_participants' => $totalParticipants,
                'total_events' => $totalEvents,
                'total_reservations' => $totalReservations,
                'total_revenue' => round($totalRevenue, 2),
                'total_commission' => round($siteCommission, 2),
            ],
            'recent_events' => $recentEvents,
        ]);
    }
}
