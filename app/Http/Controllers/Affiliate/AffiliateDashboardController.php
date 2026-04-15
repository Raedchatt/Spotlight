<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Reservation;
use App\Models\Revendeur;
use App\Enums\StatutCommission;
use App\Enums\StatutReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AffiliateDashboardController extends Controller
{
    /**
     * Display the affiliate dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $revendeur = Revendeur::firstOrCreate(
            ['user_id' => $user->id],
            [
                'referral_code' => strtoupper(\Illuminate\Support\Str::random(8)),
                'balance'       => 0,
            ]
        );

        // 1. Statistics
        $stats = [
            'balance' => $revendeur->balance,
            'total_earnings' => Commission::where('revendeur_id', $revendeur->id)
                ->where('status', StatutCommission::Approved)
                ->sum('commission_revendeur'),
            'pending_earnings' => Commission::where('revendeur_id', $revendeur->id)
                ->where('status', StatutCommission::Pending)
                ->sum('commission_revendeur'),
            'sales_count' => Commission::where('revendeur_id', $revendeur->id)
                ->where('status', StatutCommission::Approved)
                ->count(),
        ];

        // 2. Commissions History
        $commissions = Commission::with('evenement:id,titre')
            ->where('revendeur_id', $revendeur->id)
            ->latest()
            ->take(10)
            ->get();

        // 3. Referrals List (Confirmed only)
        $referralsQuery = Reservation::with(['user:id,username', 'evenement:id,titre,categorie'])
            ->where('revendeur_id', $revendeur->id)
            ->where('statut', StatutReservation::Confirmed);

        // Filters for referrals
        if ($request->filled('name')) {
            $referralsQuery->whereHas('user', function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('category')) {
            $referralsQuery->whereHas('evenement', function ($q) use ($request) {
                $q->where('categorie', $request->category);
            });
        }

        $referrals = $referralsQuery->latest()->get()->map(function ($res) {
            return [
                'id'        => $res->id,
                'participant' => [
                    'id'       => $res->user->id,
                    'name'     => $res->user->username,
                    'avatar'   => null,
                ],
                'event'     => [
                    'id'        => $res->evenement->id,
                    'titre'     => $res->evenement->titre,
                    'categorie' => $res->evenement->categorie,
                ],
                'date'      => $res->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Affiliate/Dashboard', [
            'stats'       => $stats,
            'commissions' => $commissions,
            'referrals'   => $referrals,
            'filters'     => $request->only(['name', 'category']),
        ]);
    }
}
