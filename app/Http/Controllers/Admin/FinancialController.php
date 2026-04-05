<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Enums\StatutCommission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class FinancialController extends Controller
{
    /**
     * Display the financial dashboard with tabs.
     */
    public function index()
    {
        $now = now();

        // 1. Organizer Payouts (Pending, Event ended)
        $organizerPayouts = Commission::with(['reservation.user', 'evenement.organisateur.user'])
            ->where('status', StatutCommission::Pending)
            ->whereHas('evenement', function($q) use ($now) {
                $q->where('date_fin', '<=', $now);
            })
            ->get();

        // 2. Affiliate Payouts (Pending, Event ended, has reseller)
        $affiliatePayouts = Commission::with(['reservation.user', 'evenement', 'revendeur.user'])
            ->whereNotNull('revendeur_id')
            ->where('status', StatutCommission::Pending)
            ->whereHas('evenement', function($q) use ($now) {
                $q->where('date_fin', '<=', $now);
            })
            ->get();

        // 3. Payout History (Approved)
        $history = Commission::with(['reservation.user', 'evenement.organisateur.user', 'revendeur.user'])
            ->where('status', StatutCommission::Approved)
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Financials', [
            'organizerPayouts' => $organizerPayouts,
            'affiliatePayouts' => $affiliatePayouts,
            'history' => $history,
        ]);
    }

    /**
     * Approve a commission (Payout).
     */
    public function approve(Commission $commission)
    {
        if ($commission->status !== StatutCommission::Pending) {
            return back()->with('error', 'Only pending commissions can be approved.');
        }

        $commission->update(['status' => StatutCommission::Approved]);

        return back()->with('success', 'Commission approved successfully.');
    }

    /**
     * Reject a commission.
     */
    public function reject(Commission $commission)
    {
        if ($commission->status !== StatutCommission::Pending) {
            return back()->with('error', 'Only pending commissions can be rejected.');
        }

        $commission->update(['status' => StatutCommission::Reversed]);

        return back()->with('success', 'Commission rejected.');
    }
}
