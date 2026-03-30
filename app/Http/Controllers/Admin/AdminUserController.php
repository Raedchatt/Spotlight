<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organisateur;
use App\Models\Evenement;
use App\Enums\StatutEvenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\NotificationService;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // -------------------------------------------------------------------------
    // User CRUD Management
    // -------------------------------------------------------------------------

    public function index()
    {
        $users = User::where('role', '!=', 'administrateur')
            ->latest('created_at')
            ->paginate(10);
        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:participant,organisateur,administrateur',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->back()->with('success', 'Utilisateur créé avec succès.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:participant,organisateur,administrateur',
            'password' => 'nullable|string|min:8',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        // Add basic protection against self-deletion
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->back()->with('success', 'Utilisateur supprimé avec succès.');
    }

    // -------------------------------------------------------------------------
    // Organizer Management
    // -------------------------------------------------------------------------

    /**
     * List all pending organizer applications.
     */
    public function pendingOrganizers()
    {
        $organisateurs = Organisateur::pending()
            ->with('user:id,username,email')
            ->latest()
            ->get();

        return response()->json([
            'organisateurs' => $organisateurs,
            'total' => $organisateurs->count(),
        ]);
    }

    /**
     * Approve an organizer application.
     */
    public function approveOrganizer(Organisateur $organisateur)
    {
        $organisateur->update(['statut' => 'approved']);
        
        // Ensure the user's role is set correctly (if not already)
        if ($organisateur->user->role !== \App\Enums\Role::Organisateur) {
            $organisateur->user->update(['role' => \App\Enums\Role::Organisateur]);
        }

        return response()->json([
            'message' => 'Organizer approved successfully.',
            'organisateur' => $organisateur->load('user'),
        ]);
    }

    /**
     * Reject an organizer application.
     */
    public function rejectOrganizer(Organisateur $organisateur)
    {
        $organisateur->update(['statut' => 'rejected']);

        return response()->json([
            'message' => 'Organizer rejected successfully.',
            'organisateur' => $organisateur->load('user'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Event Management
    // -------------------------------------------------------------------------

    /**
     * Validate an event in progress to open it.
     */
    public function validEvent($id)
    {
        $event = Evenement::findOrFail($id);
        
        try {
            Auth::user()->validEvent($event);
            return response()->json([
                'message' => 'Événement ouvert avec succès.',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // -------------------------------------------------------------------------
    // User Blocking
    // -------------------------------------------------------------------------

    /**
     * Block a user (permanently or temporarily).
     */
    public function bloquerUtilisateur(Request $request, User $user)
    {
        $request->validate([
            'days' => 'nullable|integer|min:1',
        ]);

        try {
            Auth::user()->bloquerCompte($user, $request->days);
            
            $message = $request->days 
                ? "Utilisateur bloqué pour {$request->days} jours." 
                : "Utilisateur bloqué définitivement.";

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unblock a user.
     */
    public function debloquerUtilisateur(User $user)
    {
        try {
            Auth::user()->debloquerCompte($user);
            
            return redirect()->back()->with('success', 'Utilisateur débloqué avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Financial Management
    // -------------------------------------------------------------------------

    /**
     * Transfer funds for a successful event to the organizer, taking 10% commission.
     */
    public function transfererFondsEvenement(Evenement $evenement)
    {
        // 1. Get all successful payments for this event that haven't been transferred yet
        $payments = \App\Models\Paiement::whereHas('reservation', function($q) use ($evenement) {
            $q->where('evenement_id', $evenement->id);
        })
        ->where('statut', \App\Enums\StatutPaiement::Succeeded)
        ->whereNull('transferred_at')
        ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'message' => 'Aucun fonds disponible pour le transfert ou fonds déjà transférés.'
            ], 400);
        }

        $totalRevenue = $payments->sum('montant');
        $commissionRate = 0.10; // 10%
        $commissionAmount = $totalRevenue * $commissionRate;
        $transferAmount = $totalRevenue - $commissionAmount;

        // 2. Mark payments as transferred
        foreach ($payments as $payment) {
            $payment->update([
                'statut' => \App\Enums\StatutPaiement::Transferred,
                'transferred_at' => now(),
            ]);
        }

        // 3. Log or record the transfer details (could be expanded to a 'Transfer' model)
        return response()->json([
            'message' => 'Fonds transférés avec succès à l\'organisateur.',
            'event' => $evenement->titre,
            'organizer' => $evenement->organisateur->username,
            'financial_breakdown' => [
                'total_revenue' => round($totalRevenue, 2),
                'site_commission_10' => round($commissionAmount, 2),
                'amount_transferred' => round($transferAmount, 2),
                'currency' => 'TND'
            ]
        ]);
    }
}
