<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $counts = [];

        if ($user && $user->role->value === 'administrateur') {
            $counts['event_validation'] = \App\Models\Evenement::where('statut', '=', 'en_attente', 'and')->count();
            $counts['financials'] = \App\Models\Evenement::where('date_fin', '<', now())
                ->where('statut', '!=', 'annule')
                ->where('is_paid_out', false)
                ->whereHas('reservations.paiement', function($q) {
                    $q->where('statut', '=', \App\Enums\StatutPaiement::Succeeded);
                })
                ->count();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? $request->user()->load(['organisateur', 'revendeur']) : null,
                'organisateur_has_stripe' => $request->user()?->organisateur?->has_stripe_account ?? false,
                'has_collaborations' => $request->user() ? \App\Models\EventCollaborator::where('organizer_id', '=', $request->user()->id, 'and')->where('statut', '=', 'accepted', 'and')->exists() : false,
                'pending_invitations' => collect($request->user() ? \App\Models\EventCollaborator::with('evenement.organisateur')->where('organizer_id', '=', $request->user()->id, 'and')->where('statut', '=', 'pending', 'and')->get() : [])->map(function($collab) {
                    return [
                        'id' => $collab->id,
                        'evenement_id' => $collab->evenement_id,
                        'titre' => $collab->evenement->titre ?? 'Unknown Event',
                        'organisateur' => $collab->evenement->organisateur->username ?? 'Unknown Organizer',
                        'date_debut' => $collab->evenement->date_debut,
                        'statut' => $collab->statut,
                        'created_at' => $collab->created_at,
                    ];
                }),
                'pending_invitations_count' => $request->user() ? \App\Models\EventCollaborator::where('organizer_id', $request->user()->id)->where('statut', 'pending')->count() : 0,
            ],
            'sidebar_counts' => $counts,
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
