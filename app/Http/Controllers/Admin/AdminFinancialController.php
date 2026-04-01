<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\FinancialRecord;
use App\Services\PayoutService;

class AdminFinancialController extends Controller
{
    /**
     * Display the financials page with a list of concluded events.
     */
    public function index()
    {
        // Eligible: ended in the past, not cancelled.
        $events = Evenement::with('organisateur.organisateur')
            ->where('date_fin', '<', now())
            ->where('statut', '!=', 'annule')
            ->latest()
            ->get()
            ->map(function ($event) {
                // Calculate revenue using FinancialRecords
                $grossRevenueCents = FinancialRecord::where('evenement_id', $event->id)
                    ->where('type', 'payment')
                    ->where('status', 'completed')
                    ->sum('amount');
                    
                $refundsCents = FinancialRecord::where('evenement_id', $event->id)
                    ->where('type', 'refund')
                    ->where('status', 'completed')
                    ->sum('amount');
                    
                $netRevenueCents = $grossRevenueCents - $refundsCents;
                
                $commissionCents = (int) floor(($netRevenueCents * 10) / 100);
                $netPayoutCents = $netRevenueCents - $commissionCents;
                
                return [
                    'id' => $event->id,
                    'titre' => $event->titre,
                    'organisateur' => [
                         'name' => $event->organisateur->username ?? 'Unknown',
                         'stripe_account_id' => $event->organisateur->organisateur->stripe_account_id ?? null,
                    ],
                    'date_fin' => $event->date_fin->format('Y-m-d H:i'),
                    'revenue' => round($netRevenueCents / 100, 2),
                    'commission' => round($commissionCents / 100, 2),
                    'net_payout' => round($netPayoutCents / 100, 2),
                    'is_paid_out' => $event->is_paid_out,
                    'paid_out_at' => $event->paid_out_at ? $event->paid_out_at->format('Y-m-d H:i') : null,
                ];
            });

        $pending = $events->filter(fn($e) => !$e['is_paid_out'])->values();
        $history = $events->filter(fn($e) => $e['is_paid_out'])->values();

        // Calculate global totals for the dashboard
        $totalPaymentsCents = FinancialRecord::where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalRefundsCents = FinancialRecord::where('type', 'refund')
            ->where('status', 'completed')
            ->sum('amount');
            
        $totalPayoutsCents = FinancialRecord::where('type', 'payout')
            ->where('status', 'completed')
            ->sum('amount');

        $platformProfitCents = $totalPaymentsCents - $totalRefundsCents - $totalPayoutsCents;

        return Inertia::render('Admin/Financials/Index', [
            'pending_payouts' => $pending,
            'payout_history' => $history,
            'stats' => [
                'total_payments' => round($totalPaymentsCents / 100, 2),
                'total_refunds' => round($totalRefundsCents / 100, 2),
                'total_payouts' => round($totalPayoutsCents / 100, 2),
                'platform_profit' => round($platformProfitCents / 100, 2),
            ]
        ]);
    }

    /**
     * Execute a Stripe transfer to the organizer.
     */
    public function pay(Evenement $event, PayoutService $payoutService)
    {
        if ($event->is_paid_out) {
            return back()->with('error', 'This event has already been paid out.');
        }

        $orgProfile = $event->organisateur->organisateur ?? null;
        if (!$orgProfile || !$orgProfile->stripe_account_id) {
            return back()->with('error', 'Organizer does not have a connected Stripe account.');
        }

        try {
            $payoutService->processEventPayout($event);
            
            // Check if it got paid
            $event->refresh();
            if ($event->is_paid_out) {
                // Get the accurate payout value tracked in records
                $netPayoutCents = FinancialRecord::where('evenement_id', $event->id)
                    ->where('type', 'payout')
                    ->where('status', 'completed')
                    ->sum('amount');
                    
                // Notify Organizer
                app(\App\Services\NotificationService::class)->notifieOrganisateurPaiementEffectue(
                    $event->organisateur_id, 
                    $event->titre, 
                    round($netPayoutCents / 100, 2)
                );
                
                return back()->with('success', 'Transfer successfully completed via Stripe.');
            } else {
                return back()->with('error', 'No revenue generated to transfer, or transfer skipped.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stripe Transfer failed: ' . $e->getMessage());
            return back()->with('error', 'Stripe Transfer failed: ' . $e->getMessage());
        }
    }
}
