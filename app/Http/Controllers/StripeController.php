<?php

namespace App\Http\Controllers;

use App\Enums\StatutPaiement;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // ─────────────────────────────────────────────
    // 1. CREATE CHECKOUT SESSION
    // ─────────────────────────────────────────────
    public function createCheckoutSession(Request $request, Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $evenement = $reservation->evenement;

        // Determine unit price based on ticket type
        $unitPrice = (float) $evenement->prix_spectateur;
        if ($evenement->is_tournoi && $reservation->ticket_type === 'participant') {
            $unitPrice = (float) $evenement->prix_participant;
        }

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'eur', // TND is not supported by default, switching back to EUR
                        'unit_amount'  => (int) ($unitPrice * 100),
                        'product_data' => [
                            'name'        => $evenement->titre . " (" . ucfirst($reservation->ticket_type ?? 'standard') . ")",
                            'description' => $evenement->description ?? '',
                        ],
                    ],
                    'quantity' => $reservation->nombre_tickets,
                ]],
                'mode'        => 'payment',
                'success_url' => route('paiement.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('paiement.cancel'),
                'metadata'    => [
                    'reservation_id' => $reservation->id,
                    'user_id'        => Auth::id(),
                ],
            ]);

            Paiement::create([
                'reservation_id'    => $reservation->id,
                'montant'           => $unitPrice * $reservation->nombre_tickets,
                'currency'          => 'eur',
                'stripe_session_id' => $session->id,
                'payment_method'    => 'card',
                'statut'            => StatutPaiement::Pending,
            ]);

            return response()->json([
                'checkout_url' => $session->url,
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe session creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment initialization failed'], 500);
        }
    }

    // ─────────────────────────────────────────────
    // 2. SUCCESS
    // ─────────────────────────────────────────────
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        $paiement = Paiement::where('stripe_session_id', $sessionId)->first();

        if (!$paiement) {
            return redirect()->route('discovery')->with('error', 'Paiement introuvable.');
        }

        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        if ($session->payment_status === 'paid') {
            $paiement->update([
                'statut'                   => StatutPaiement::Succeeded,
                'stripe_payment_intent_id' => $session->payment_intent,
                'transferred_at'           => now(),
            ]);
            
            $paiement->reservation->confirm();
        }

        return redirect()->route('discovery')->with('success', 'Paiement effectué avec succès !');
    }

    // ─────────────────────────────────────────────
    // 3. CANCEL
    // ─────────────────────────────────────────────
    public function cancel()
    {
        return redirect()->route('discovery')->with('error', 'Paiement annulé.');
    }

    // ─────────────────────────────────────────────
    // 4. WEBHOOK
    // ─────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {

            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                Paiement::where('stripe_payment_intent_id', $paymentIntent->id)
                    ->update([
                        'statut'         => StatutPaiement::Succeeded,
                        'transferred_at' => now(),
                    ]);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                Paiement::where('stripe_payment_intent_id', $paymentIntent->id)
                    ->update(['statut' => StatutPaiement::Failed]);
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                Paiement::where('stripe_payment_intent_id', $charge->payment_intent)
                    ->update(['statut' => StatutPaiement::Refunded]);
                break;
        }

        return response()->json(['status' => 'ok']);
    }
}