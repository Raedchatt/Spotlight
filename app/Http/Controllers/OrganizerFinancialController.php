<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\FinancialRecord;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrganizerFinancialController extends Controller
{
    /**
     * Get financial statistics for the logged-in organizer.
     * GET /api/organizer/financials
     * GET /dashboard/financials
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        // Get all events owned by this organizer
        $events = Evenement::where('organisateur_id', $userId)
            ->latest()
            ->get();

        $eventIds = $events->pluck('id')->toArray();

        // Awaiting payout: Payments - Refunds for events that are NOT paid out yet
        $awaitingPayoutEvents = $events->where('is_paid_out', false);
        
        $awaitingPayoutCents = 0;
        foreach ($awaitingPayoutEvents as $event) {
            $revenueCents = FinancialRecord::where('evenement_id', $event->id)
                ->where('type', 'payment')
                ->where('status', 'completed')
                ->sum('amount');
                
            $refundCents = FinancialRecord::where('evenement_id', $event->id)
                ->where('type', 'refund')
                ->where('status', 'completed')
                ->sum('amount');
                
            $net = $revenueCents - $refundCents;
            if ($net > 0) {
                // Apply 10% commission
                $commission = (int) floor(($net * 10) / 100);
                $awaitingPayoutCents += ($net - $commission);
            }
        }

        // Total Paid: Sum of all payouts
        $totalPaidCents = FinancialRecord::whereIn('evenement_id', $eventIds)
            ->where('type', 'payout')
            ->where('status', 'completed')
            ->sum('amount');

        // Events list with revenue and payout status
        $eventsList = $events->map(function ($event) {
            // Gross revenue
            $revenueCents = FinancialRecord::where('evenement_id', $event->id)
                ->where('type', 'payment')
                ->where('status', 'completed')
                ->sum('amount');
                
            $refundCents = FinancialRecord::where('evenement_id', $event->id)
                ->where('type', 'refund')
                ->where('status', 'completed')
                ->sum('amount');
                
            $grossNet = $revenueCents - $refundCents;
            
            return [
                'id' => $event->id,
                'titre' => $event->titre,
                'date_fin' => $event->date_fin->format('Y-m-d H:i:s'),
                'gross_revenue' => round($grossNet / 100, 2),
                'status' => $event->statut,
                'is_paid_out' => $event->is_paid_out,
                'paid_out_at' => $event->paid_out_at ? $event->paid_out_at->format('Y-m-d H:i:s') : null,
            ];
        });

        // Payout history strictly from records
        $payoutHistory = FinancialRecord::whereIn('evenement_id', $eventIds)
            ->where('type', 'payout')
            ->where('status', 'completed')
            ->with('evenement:id,titre')
            ->latest()
            ->get()
            ->map(fn($record) => [
                'id'         => $record->id,
                'event_titre'=> $record->evenement->titre ?? 'Unknown',
                'amount'     => round($record->amount / 100, 2),
                'currency'   => $record->currency,
                'reference'  => $record->stripe_reference,
                'created_at' => $record->created_at->format('Y-m-d H:i')
            ]);

        $data = [
            'stats' => [
                'awaiting_payout' => round($awaitingPayoutCents / 100, 2),
                'total_paid' => round($totalPaidCents / 100, 2),
                'currency' => 'EUR',
            ],
            'events' => $eventsList,
            'payout_history' => $payoutHistory
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Organizer/Financials', $data);
    }
}
