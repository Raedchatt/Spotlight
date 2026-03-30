<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Illuminate\Support\Facades\Log;

class StripeConnectController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function connect(Request $request)
    {
        $user = Auth::user();
        $organisateur = $user->organisateur;

        if (!$organisateur) {
            return response()->json(['error' => 'You do not have an organizer profile.'], 403);
        }

        try {
            // Re-use existing Stripe Account ID or create a new one
            $stripeAccountId = $organisateur->stripe_account_id;

            if (!$stripeAccountId) {
                $account = Account::create([
                    'type' => 'express',
                    'email' => $user->email,
                    'business_type' => 'individual',
                ]);
                $stripeAccountId = $account->id;
                
                $organisateur->update(['stripe_account_id' => $stripeAccountId]);
            }

            // Create Account Link for onboarding
            $accountLink = AccountLink::create([
                'account' => $stripeAccountId,
                'refresh_url' => route('stripe.connect.refresh'),
                'return_url'  => route('stripe.connect.return'),
                'type'        => 'account_onboarding',
            ]);

            return response()->json(['url' => $accountLink->url]);

        } catch (\Exception $e) {
            Log::error('Stripe Connect error: ' . $e->getMessage());
            
            $msg = 'Unable to connect to Stripe right now.';
            if (str_contains($e->getMessage(), 'signed up for Connect')) {
                $msg = 'Stripe Connect is not enabled on this platform. The administrator must enable it at dashboard.stripe.com/connect before organizers can sign up.';
            }

            return response()->json(['error' => $msg], 500);
        }
    }

    public function returnHandler(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->organisateur || !$user->organisateur->stripe_account_id) {
            return redirect()->route('profile.edit')->with('error', 'Stripe account not found.');
        }

        try {
            // Verify if details were submitted
            $account = Account::retrieve($user->organisateur->stripe_account_id);
            
            if ($account->details_submitted) {
                return redirect()->route('profile.edit')->with('success', 'Stripe account connected successfully!');
            } else {
                return redirect()->route('profile.edit')->with('message', 'Stripe onboarding incomplete. Please finish setting up your account.');
            }
        } catch (\Exception $e) {
            Log::error('Stripe Connect verification error: ' . $e->getMessage());
            return redirect()->route('profile.edit')->with('error', 'Error verifying Stripe account.');
        }
    }

    public function refreshHandler(Request $request)
    {
        // Redirect to profile, asking them to click "Connect with Stripe" again
        return redirect()->route('profile.edit')->with('message', 'Your Stripe session expired. Please try connecting again.');
    }
}
