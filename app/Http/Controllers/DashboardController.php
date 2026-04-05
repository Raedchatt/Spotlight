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

        if ($user->role === Role::Organisateur) {
            return $this->organizerDashboard();
        }

        if ($user->role === Role::Revendeur) {
            return app(\App\Http\Controllers\Affiliate\AffiliateDashboardController::class)->index(request());
        }

        // Default for participants/others
        return Inertia::render('Dashboard', [
            'dashboardData' => null
        ]);
    }

    protected function organizerDashboard()
    {
        $user = Auth::user();

        // 1. Basic counts
        $totalEventsCount = Evenement::where('organisateur_id', $user->id)->count();

        // 2. Revenue calculation (total received)
        $totalReceived = Paiement::whereHas('reservation', function($q) use ($user) {
            $q->whereHas('evenement', function($eq) use ($user) {
                $eq->where('organisateur_id', '=', $user->id);
            });
        })->where('statut', '=', StatutPaiement::Succeeded)->sum('montant');

        // 3. Pending Payout (events where is_paid_out = false)
        $pendingPayout = Paiement::whereHas('reservation', function($q) use ($user) {
            $q->whereHas('evenement', function($eq) use ($user) {
                $eq->where('organisateur_id', '=', $user->id)
                   ->where('is_paid_out', '=', false);
            });
        })->where('statut', '=', StatutPaiement::Succeeded)->sum('montant');

        // 4. Category breakdown
        $categoryCounts = Evenement::where('organisateur_id', $user->id)
            ->select('categorie', \DB::raw('count(*) as count'))
            ->groupBy('categorie')
            ->get();

        $categoryBreakdown = $categoryCounts->map(function($cat) use ($totalEventsCount) {
             return [
                 'category' => $cat->categorie instanceof \App\Enums\CategorieEvenement ? $cat->categorie->value : $cat->categorie,
                 'count'    => $cat->count,
                 'percent'  => $totalEventsCount > 0 ? round(($cat->count / $totalEventsCount) * 100) : 0
             ];
        });

        // 5. Current events (Latest 5 with revenue)
        $currentEvents = Evenement::where('organisateur_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function($event) {
                $revenue = Paiement::whereHas('reservation', function($q) use ($event) {
                    $q->where('evenement_id', '=', $event->id);
                })->where('statut', '=', StatutPaiement::Succeeded)->sum('montant');

                return [
                    'id'        => $event->id,
                    'titre'     => $event->titre,
                    'categorie' => $event->categorie instanceof \App\Enums\CategorieEvenement ? $event->categorie->value : $event->categorie,
                    'lieu'      => $event->lieu,
                    'statut'    => $event->statut instanceof \App\Enums\StatutEvenement ? $event->statut->value : $event->statut,
                    'revenue'   => round($revenue, 2),
                ];
            });

        // 6. Active Participants (Top 10 users by ticket count across all organizer events)
        $activeParticipants = User::whereHas('reservations', function($q) use ($user) {
            $q->whereHas('evenement', function($eq) use ($user) {
                $eq->where('organisateur_id', '=', $user->id);
            });
        })->withCount(['reservations as ticket_count' => function($q) use ($user) {
             $q->whereHas('evenement', function($eq) use ($user) {
                $eq->where('organisateur_id', '=', $user->id);
            })->select(\DB::raw('sum(nombre_tickets)'));
        }])
        ->orderBy('ticket_count', 'desc')
        ->take(10)
        ->get()
        ->map(function($p) {
             return [
                 'id'          => $p->id,
                 'username'    => $p->username,
                 'avatar'      => null,
                 'event_count' => (int) $p->ticket_count,
             ];
        });

        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'totalReceived'      => round($totalReceived, 2),
                'pendingPayout'      => round($pendingPayout, 2),
                'categoryBreakdown'  => $categoryBreakdown,
                'currentEvents'      => $currentEvents,
                'activeParticipants' => $activeParticipants,
                'totalEvents'        => $totalEventsCount,
            ]
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
