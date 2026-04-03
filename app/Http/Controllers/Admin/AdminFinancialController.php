<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Stripe;
use Stripe\Transfer;

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
                // Calculate revenue and admin commission
                $eventRevenue = \App\Models\Paiement::whereHas('reservation', function($q) use ($event) {
                    $q->where('evenement_id', '=', $event->id);
                })->where('statut', '=', \App\Enums\StatutPaiement::Succeeded)->sum('montant');
                
                $adminCommissionTotal = \App\Models\Commission::where('evenement_id', $event->id)
                    ->whereHas('reservation', function($q) {
                        $q->where('statut', \App\Enums\StatutReservation::Confirmed);
                    })
                    ->sum('commission_admin');

                $netPayout = $eventRevenue * 0.80;
                
                return [
                    'id' => $event->id,
                    'titre' => $event->titre,
                    'organisateur' => [
                         'name' => $event->organisateur->username ?? 'Unknown',
                         'stripe_account_id' => $event->organisateur->organisateur->stripe_account_id ?? null,
                    ],
                    'date_fin' => $event->date_fin->format('Y-m-d H:i'),
                    'revenue' => round($eventRevenue, 2),
                    'commission' => round($adminCommissionTotal, 2),
                    'net_payout' => round($netPayout, 2),
                    'is_paid_out' => $event->is_paid_out,
                    'paid_out_at' => $event->paid_out_at ? $event->paid_out_at->format('Y-m-d H:i') : null,
                ];
            });

        $pending = $events->filter(fn($e) => !$e['is_paid_out'])->values();
        $history = $events->filter(fn($e) => $e['is_paid_out'])->values();

        return Inertia::render('Admin/Financials/Index', [
            'pending_payouts' => $pending,
            'payout_history' => $history,
        ]);
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

        // Calculate payout
        $eventRevenue = \App\Models\Paiement::whereHas('reservation', function($q) use ($event) {
            $q->where('evenement_id', '=', $event->id);
        })->where('statut', '=', \App\Enums\StatutPaiement::Succeeded)->sum('montant');

        if ($eventRevenue <= 0) {
            return back()->with('error', 'No revenue generated to transfer.');
        }

        $netPayout = $eventRevenue * 0.80; // Organizer always gets 80%
        $transferAmount = (int) round($netPayout * 100); // Stripe expects cents

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            // Create a Transfer to the connected account
            $transfer = Transfer::create([
                'amount' => $transferAmount,
                'currency' => 'eur',
                'destination' => $orgProfile->stripe_account_id,
                'metadata' => [
                    'evenement_id' => $event->id,
                    'evenement_titre' => $event->titre,
                ],
            ]);

            // Update database
            $event->update([
                'is_paid_out' => true,
                'paid_out_at' => now(),
            ]);

            // Notify Organizer
            app(\App\Services\NotificationService::class)->notifieOrganisateurPaiementEffectue(
                $event->organisateur_id, 
                $event->titre, 
                $netPayout
            );

            return back()->with('success', 'Transfer successfully completed via Stripe.');

        } catch (\Stripe\Exception\ApiErrorException $e) {
            \Illuminate\Support\Facades\Log::error('Stripe Transfer failed: ' . $e->getMessage());
            return back()->with('error', 'Stripe Transfer failed: ' . $e->getMessage());
        }
    }
}
