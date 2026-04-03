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

class AdminFinancialController extends Controller
{
    /**
     * Display the financials page with 3 sections.
     */
    public function index()
    {
        $now = now();
        $waitPeriod = $now->subDay();

        // 1. Organizer Payouts (Events that finished > 1 day ago)
        $organizerPayouts = Evenement::with('organisateur')
            ->where('date_fin', '<=', $waitPeriod)
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
            });

        // 2. Affiliate Payouts (Pending, Event ended > 1 day ago)
        $affiliatePayouts = Commission::with(['reservation.user', 'evenement', 'revendeur.user'])
            ->whereNotNull('revendeur_id')
            ->where('status', StatutCommission::Pending)
            ->whereHas('evenement', function($q) use ($waitPeriod) {
                $q->where('date_fin', '<=', $waitPeriod);
            })
            ->get()
            ->map(function ($comm) {
                return [
                    'id' => $comm->id,
                    'reseller_name' => $comm->revendeur->user->username ?? 'Unknown',
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
     * Approve an affiliate commission (Payout).
     */
    public function approveAffiliate(Commission $commission)
    {
        $commission->update(['status' => StatutCommission::Approved]);
        return back()->with('success', 'Affiliate payout marked as completed.');
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
}
