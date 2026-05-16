<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Models\User;
use App\Enums\StatutReservation;
use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminReservationController extends Controller
{
    /**
     * Display a paginated list of all reservations with filters.
     */
    public function index(Request $request)
    {
        $query = Reservation::with([
            'user:id,username,email',
            'evenement:id,titre,date_debut,lieu,is_tournoi,poster_url',
        ]);

        // Filter by participant name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by event title
        if ($request->filled('event')) {
            $event = $request->event;
            $query->whereHas('evenement', function ($q) use ($event) {
                $q->where('titre', 'like', "%{$event}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('statut', $request->status);
        }

        $reservations = $query->latest()->paginate(20)->withQueryString();
        $totalReservations = Reservation::count();

        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations,
            'totalReservations' => $totalReservations,
            'filters' => $request->only(['search', 'event', 'status']),
        ]);
    }

    /**
     * Create a reservation on behalf of a participant (admin only).
     * Skips payment — auto-confirmed.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_identifier' => 'required|string',
            'evenement_id' => 'required|exists:evenements,id',
            'nombre_tickets' => 'required|integer|min:1',
            'ticket_type' => 'nullable|string|in:standard,spectator,participant',
        ]);

        // Resolve the user from identifier (try ID, then email, then username)
        $identifier = trim($request->user_identifier);
        $user = null;

        if (is_numeric($identifier)) {
            $user = User::find((int) $identifier);
        }

        if (!$user) {
            $user = User::where('email', $identifier)->first();
        }

        if (!$user) {
            $user = User::where('username', $identifier)->first();
        }

        if (!$user) {
            return back()->withErrors(['user_identifier' => 'No user found with this identifier.']);
        }

        // Check the user is a participant
        if ($user->role !== Role::Participant) {
            return back()->withErrors(['user_identifier' => 'The selected user is not a participant.']);
        }

        $evenement = Evenement::with('tournoi')->findOrFail($request->evenement_id);

        // Determine ticket type
        $ticketType = $request->ticket_type ?? 'standard';
        if ($evenement->is_tournoi && $ticketType === 'standard') {
            $ticketType = 'spectator';
        }

        // Check capacity
        if ($evenement->is_tournoi && $ticketType === 'participant') {
            $currentReservations = $evenement->reservations()
                ->active()
                ->where('ticket_type', 'participant')
                ->sum('nombre_tickets');

            $participantCapacity = ($evenement->type_tournoi === 'equipe' && $evenement->tournoi
                    && $evenement->tournoi->nombre_equipes > 0 && $evenement->tournoi->joueurs_par_equipe > 0)
                ? ($evenement->tournoi->nombre_equipes * $evenement->tournoi->joueurs_par_equipe)
                : $evenement->capacite_participant;

            $availableSpaces = $participantCapacity - $currentReservations;
        } else {
            $currentReservations = $evenement->reservations()
                ->active()
                ->where(function ($query) {
                    $query->where('ticket_type', 'spectator')
                        ->orWhere('ticket_type', 'standard')
                        ->orWhereNull('ticket_type');
                })
                ->sum('nombre_tickets');
            $availableSpaces = $evenement->capacite_spectateur - $currentReservations;
        }

        if ($request->nombre_tickets > $availableSpaces) {
            $typeLabel = $ticketType === 'participant' ? 'participant' : 'spectator';
            return back()->withErrors([
                'nombre_tickets' => "Not enough {$typeLabel} spaces. Only {$availableSpaces} spots left."
            ]);
        }

        // Create reservation — auto-confirmed, skip payment
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'evenement_id' => $evenement->id,
            'ticket_type' => $ticketType,
            'nombre_tickets' => $request->nombre_tickets,
            'note' => 'Created by admin',
            'statut' => StatutReservation::Confirmed,
        ]);

        return back()->with('success', "Reservation created for {$user->username} ({$request->nombre_tickets} tickets).");
    }

    /**
     * Search users (participants) for the admin reservation modal.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q', '');

        $users = User::where('role', Role::Participant)
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'username', 'email']);

        return response()->json($users);
    }

    /**
     * Search events for the admin reservation modal.
     */
    public function searchEvents(Request $request)
    {
        $query = $request->input('q', '');

        $events = Evenement::where('titre', 'like', "%{$query}%")
            ->with('tournoi')
            ->limit(10)
            ->get(['id', 'titre', 'is_tournoi', 'capacite_spectateur', 'capacite_participant', 'type_tournoi', 'prix_spectateur', 'prix_participant']);

        return response()->json($events);
    }

    /**
     * Cancel any reservation (admin override — no 3-day rule, no ownership check).
     */
    public function cancel(Reservation $reservation)
    {
        if ($reservation->statut === StatutReservation::Cancelled) {
            return back()->withErrors(['reservation' => 'This reservation is already cancelled.']);
        }

        if ($reservation->evenement->date_debut->isPast()) {
            return back()->withErrors(['reservation' => 'Cannot cancel a reservation for an event that has already started or passed.']);
        }

        $reservation->cancel();

        return back()->with('success', "Reservation #{$reservation->id} cancelled successfully.");
    }
}
