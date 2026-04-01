<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialRecord;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialDashboardController extends Controller
{
    /**
     * Get aggregate statistics and filtered records for the admin financial dashboard.
     * GET /api/admin/financials
     */
    public function index(Request $request)
    {
        $query = FinancialRecord::with(['evenement.organisateur', 'paiement']);

        // Filters
        if ($request->has('evenement_id')) {
            $query->where('evenement_id', $request->evenement_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('organisateur_id')) {
            $orgId = $request->organisateur_id;
            $query->whereHas('evenement', function ($q) use ($orgId) {
                // organizer linked to event
                $q->where('organisateur_id', $orgId);
            });
        }

        // Paginating table records
        $records = $query->latest()->paginate(20)->through(function ($record) {
            return [
                'id' => $record->id,
                'type' => $record->type,
                'amount' => $record->amount / 100, // display as standard currency
                'currency' => $record->currency,
                'event' => $record->evenement ? $record->evenement->titre : null,
                'organizer' => $record->evenement && $record->evenement->organisateur ? 
                               $record->evenement->organisateur->username : null,
                'status' => $record->status,
                'reference' => $record->stripe_reference,
                'created_at' => $record->created_at->format('Y-m-d H:i:s'),
            ];
        });

        // Calculate global totals
        // For accurate tracking, sum from financial_records where status = completed
        $totalPaymentsCents = FinancialRecord::where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalRefundsCents = FinancialRecord::where('type', 'refund')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalPayoutsCents = FinancialRecord::where('type', 'payout')
            ->where('status', 'completed')
            ->sum('amount');

        // Platform Profit: Sum of payments minus (refunds + payouts)
        $platformProfitCents = $totalPaymentsCents - $totalRefundsCents - $totalPayoutsCents;

        return response()->json([
            'stats' => [
                'total_payments' => round($totalPaymentsCents / 100, 2),
                'total_refunds' => round($totalRefundsCents / 100, 2),
                'total_payouts' => round($totalPayoutsCents / 100, 2),
                'platform_commission_profit' => round($platformProfitCents / 100, 2),
                'currency' => 'EUR'
            ],
            'records' => $records
        ]);
    }
}
