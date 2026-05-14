<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use App\Models\Commission;
use App\Enums\StatutCommission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Transfer;
use Stripe\Refund;

class AdminFinancialController extends Controller
{
    /**
     * Display the financials page with 3 sections.
     */
    public function index()
    {
        $now = now();

        // 1. Organizer Payouts (Events that finished)
        $organizerPayouts = Evenement::with('organisateur')
            ->where('date_fin', '<=', $now)
            ->where('is_paid_out', false)
            ->where('statut', '!=', 'annule')
            ->latest()
            ->get()
            ->map(function ($event) {
                $revenue = \App\Models\Paiement::whereHas('reservation', function($q) use ($event) {
                    $q->where('evenement_id', '=', $event->id);
                })->where('statut', '=', \App\Enums\StatutPaiement::Succeeded)->sum('montant');
                
                $commission = Commission::where('evenement_id', $event->id)->sum('commission_admin');
                $net = $revenue * 0.80;

                return [
                    'id' => $event->id,
                    'titre' => $event->titre,
                    'organisateur' => [
                        'name' => $event->organisateur->username ?? 'Unknown',
                        'stripe_account_id' => $event->organisateur->organisateur->stripe_account_id ?? null,
                    ],
                    'date_fin' => $event->date_fin->format('Y-m-d H:i'),
                    'revenue' => round($revenue, 2),
                    'commission' => round($commission, 2),
                    'net_payout' => round($net, 2),
                ];
            })
            ->filter(fn($payout) => $payout['revenue'] > 0)
            ->values();

        // 2. Affiliate Payouts (Pending, Event ended)
        $affiliatePayouts = Commission::with(['reservation.user', 'evenement', 'revendeur.user'])
            ->whereNotNull('revendeur_id')
            ->where('status', StatutCommission::Pending)
            ->whereHas('evenement', function($q) use ($now) {
                $q->where('date_fin', '<=', $now);
            })
            ->get()
            ->map(function ($comm) {
                return [
                    'id' => $comm->id,
                    'reseller_name' => $comm->revendeur->user->username ?? 'Unknown',
                    'reseller_email' => $comm->revendeur->user->email ?? '',
                    'stripe_account_id' => $comm->revendeur->stripe_account_id ?? null,
                    'event_title' => $comm->evenement->titre,
                    'amount' => $comm->commission_revendeur,
                    'date_fin' => $comm->evenement->date_fin->format('Y-m-d H:i'),
                    'status' => $comm->status,
                ];
            });

        // 3. Payout History (Approved or is_paid_out)
        $history = Commission::with(['reservation.user', 'evenement', 'revendeur.user'])
            ->where('status', StatutCommission::Approved)
            ->latest()
            ->get()
            ->map(function ($comm) {
                return [
                    'type' => 'Affiliate',
                    'recipient' => $comm->revendeur->user->username ?? 'Unknown',
                    'event' => $comm->evenement->titre,
                    'amount' => $comm->commission_revendeur,
                    'date' => $comm->updated_at->format('Y-m-d H:i'),
                ];
            })->concat(
                Evenement::where('is_paid_out', true)
                ->with('organisateur')
                ->latest()
                ->get()
                ->map(function ($event) {
                    $revenue = \App\Models\Paiement::whereHas('reservation', function($q) use ($event) {
                        $q->where('evenement_id', '=', $event->id);
                    })->where('statut', '=', \App\Enums\StatutPaiement::Succeeded)->sum('montant');
                    
                    return [
                        'type' => 'Organizer',
                        'recipient' => $event->organisateur->username ?? 'Unknown',
                        'event' => $event->titre,
                        'amount' => $revenue * 0.80,
                        'date' => $event->paid_out_at ? $event->paid_out_at->format('Y-m-d H:i') : $event->updated_at->format('Y-m-d H:i'),
                    ];
                })
            )->sortByDesc('date')->values();

        return Inertia::render('Admin/Financials/Index', [
            'organizerPayouts' => $organizerPayouts,
            'affiliatePayouts' => $affiliatePayouts,
            'history' => $history,
        ]);
    }

    /**
     * Approve an affiliate commission and execute a real Stripe Transfer
     * to the reseller's connected Stripe account.
     */
    public function approveAffiliate(Commission $commission)
    {
        // 1. Guard: already approved
        if ($commission->status === StatutCommission::Approved) {
            return back()->with('error', 'This commission has already been paid out.');
        }

        // 2. Guard: reseller must have a connected Stripe account
        $revendeur = $commission->revendeur;
        if (!$revendeur || !$revendeur->stripe_account_id) {
            return back()->with('error', 'Reseller does not have a connected Stripe account.');
        }

        // 3. Calculate amount in cents
        $amount = $commission->commission_revendeur;
        if ($amount <= 0) {
            return back()->with('error', 'No commission amount to transfer.');
        }
        $transferAmount = (int) round($amount * 100);

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // 4. Execute real Stripe Transfer
            $transfer = Transfer::create([
                'amount' => $transferAmount,
                'currency' => 'eur',
                'destination' => $revendeur->stripe_account_id,
                'metadata' => [
                    'commission_id' => $commission->id,
                    'evenement_id' => $commission->evenement_id,
                    'reseller_id' => $revendeur->id,
                ],
            ]);

            // 5. Update commission with transfer proof and approved status
            $commission->update([
                'status' => StatutCommission::Approved,
                'stripe_transfer_id' => $transfer->id,
            ]);

            // Note: The Commission model's booted() observer will
            // automatically increment the reseller's balance.

            return back()->with('success', 'Affiliate payout transferred successfully via Stripe.');

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Illuminate\Support\Facades\Log::error('Stripe affiliate transfer failed: ' . $e->getMessage());
            return back()->with('error', 'Stripe Transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute a Stripe transfer to the organizer.
     */
    public function pay(Evenement $event)
    {
        if ($event->is_paid_out) {
            return back()->with('error', 'This event has already been paid out.');
        }

        $orgProfile = $event->organisateur->organisateur;
        if (!$orgProfile || !$orgProfile->stripe_account_id) {
            return back()->with('error', 'Organizer does not have a connected Stripe account.');
        }

        $eventRevenue = \App\Models\Paiement::whereHas('reservation', function($q) use ($event) {
            $q->where('evenement_id', '=', $event->id);
        })->where('statut', '=', \App\Enums\StatutPaiement::Succeeded)->sum('montant');

        if ($eventRevenue <= 0) {
            return back()->with('error', 'No revenue generated to transfer.');
        }

        $netPayout = $eventRevenue * 0.80;
        $transferAmount = (int) round($netPayout * 100);

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            Transfer::create([
                'amount' => $transferAmount,
                'currency' => 'eur',
                'destination' => $orgProfile->stripe_account_id,
                'metadata' => [
                    'evenement_id' => $event->id,
                    'evenement_titre' => $event->titre,
                ],
            ]);

            $event->update([
                'is_paid_out' => true,
                'paid_out_at' => now(),
            ]);

            // Mark all commissions for this event as Approved if they weren't already?
            // Actually, affiliate commissions might be separate. 
            // The user said "approve commission statut that mean i payout".
            // For organizer, it's this 'pay' action.

            return back()->with('success', 'Transfer successfully completed via Stripe.');

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Illuminate\Support\Facades\Log::error('Stripe Transfer failed: ' . $e->getMessage());
            return back()->with('error', 'Stripe Transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Refund participants for an event and mark it as cancelled.
     */
    public function refund(Evenement $event)
    {
        if ($event->is_paid_out) {
            return back()->with('error', 'This event has already been paid out.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $reservations = \App\Models\Reservation::where('evenement_id', '=', $event->id)
            ->where('statut', '=', \App\Enums\StatutReservation::Confirmed->value)
            ->get();

        $refundedCount = 0;
        $failedCount = 0;

        foreach ($reservations as $reservation) {
            $paiement = \App\Models\Paiement::where('reservation_id', '=', $reservation->id)
                ->where('statut', '=', \App\Enums\StatutPaiement::Succeeded->value)
                ->first();

            if ($paiement && $paiement->stripe_payment_intent_id) {
                try {
                    Refund::create([
                        'payment_intent' => $paiement->stripe_payment_intent_id,
                    ]);

                    $paiement->update(['statut' => \App\Enums\StatutPaiement::Refunded]);
                    $refundedCount++;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Refund failed for reservation {$reservation->id}: " . $e->getMessage());
                    $failedCount++;
                }
            }
            
            $reservation->update(['statut' => \App\Enums\StatutReservation::Cancelled]);
        }

        $event->update(['statut' => \App\Enums\StatutEvenement::Annule]);

        // Reverse any pending commissions
        \App\Models\Commission::where('evenement_id', $event->id)
            ->where('status', \App\Enums\StatutCommission::Pending)
            ->update(['status' => \App\Enums\StatutCommission::Reversed]);

        return back()->with('success', "Event refunded successfully. $refundedCount refunds processed.");
    }
}
