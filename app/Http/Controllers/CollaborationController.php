<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\EventCollaborator;
use App\Models\User;
use App\Enums\Role;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollaborationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * List all collaborators of an event (owner only).
     */
    public function index($eventId)
    {
        $event = Evenement::findOrFail($eventId);

        if ($event->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $collaborators = EventCollaborator::with('organizer')
            ->where('evenement_id', $eventId)
            ->get()
            ->map(function ($c) {
                return [
                    'id'         => $c->id,
                    'statut'     => $c->statut,
                    'organizer'  => [
                        'id'       => $c->organizer->id,
                        'username' => $c->organizer->username,
                        'email'    => $c->organizer->email,
                    ],
                ];
            });

        return response()->json($collaborators);
    }

    /**
     * Invite another organizer to co-organize this event.
     */
    public function invite(Request $request, $eventId)
    {
        $event = Evenement::findOrFail($eventId);

        if ($event->organisateur_id !== Auth::id()) {
            return response()->json(['message' => 'Only the event owner can invite collaborators.'], 403);
        }

        $request->validate([
            'organizer_id' => 'required|integer|exists:users,id',
        ]);

        $inviteeId = $request->input('organizer_id');

        // Cannot invite yourself
        if ($inviteeId == Auth::id()) {
            return response()->json(['message' => 'You cannot invite yourself.'], 422);
        }

        // Invitee must be an organizer
        $invitee = User::findOrFail($inviteeId);
        if ($invitee->role !== Role::Organisateur) {
            return response()->json(['message' => 'The selected user is not an organizer.'], 422);
        }

        // Check for duplicate
        $existing = EventCollaborator::where('evenement_id', $eventId)
            ->where('organizer_id', $inviteeId)
            ->first();

        if ($existing) {
            if ($existing->statut === 'accepted') {
                return response()->json(['message' => 'This organizer is already a collaborator.'], 422);
            }
            if ($existing->statut === 'pending') {
                return response()->json(['message' => 'An invitation is already pending for this organizer.'], 422);
            }
            // If previously rejected, reset to pending
            $existing->update(['statut' => 'pending']);
        } else {
            EventCollaborator::create([
                'evenement_id' => $eventId,
                'organizer_id' => $inviteeId,
                'statut'       => 'pending',
            ]);
        }

        // Send notification
        $this->notificationService->envoyerInvitationCollaboration(
            $inviteeId,
            $event->titre,
            Auth::user()->username,
            $eventId
        );

        return response()->json(['message' => 'Invitation sent successfully.']);
    }

    /**
     * Accept a collaboration invitation.
     */
    public function accept($eventId)
    {
        \Log::info('Accept collaboration called', ['event_id' => $eventId, 'auth_id' => Auth::id()]);

        $collab = EventCollaborator::where('evenement_id', $eventId)
            ->where('organizer_id', Auth::id())
            ->first();

        if (!$collab) {
            return response()->json(['message' => 'Collaboration invitation not found.'], 404);
        }

        if ($collab->statut !== 'pending') {
            return response()->json(['message' => 'This invitation has already been processed.'], 400);
        }

        $collab->update(['statut' => 'accepted']);

        $event = Evenement::findOrFail($eventId);

        $this->notificationService->notifieCollaborationAcceptee(
            $event->organisateur_id,
            Auth::user()->username,
            $event->titre
        );

        return response()->json(['message' => 'You have joined the event as a co-organizer.']);
    }

    /**
     * Reject a collaboration invitation.
     */
    public function reject($eventId)
    {
        \Log::info('Reject collaboration called', ['event_id' => $eventId, 'auth_id' => Auth::id()]);

        $collab = EventCollaborator::where('evenement_id', $eventId)
            ->where('organizer_id', Auth::id())
            ->first();

        if (!$collab) {
            return response()->json(['message' => 'Collaboration invitation not found.'], 404);
        }

        if ($collab->statut !== 'pending') {
            return response()->json(['message' => 'This invitation has already been processed.'], 400);
        }

        $collab->update(['statut' => 'rejected']);

        $event = Evenement::findOrFail($eventId);

        $this->notificationService->notifieCollaborationRejected(
            $event->organisateur_id,
            Auth::user()->username,
            $event->titre
        );

        return response()->json(['message' => 'Invitation rejected.']);
    }

    /**
     * Search organizers by username or email (for invite autocomplete).
     */
    public function searchOrganizers(Request $request)
    {
        $query = $request->input('q', '');

        $organizers = User::where('role', Role::Organisateur)
            ->where('id', '!=', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('username', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'username', 'email']);

        return response()->json($organizers);
    }

    /**
     * Get all events where the current user is an accepted collaborator.
     */
    public function myCollaborations(Request $request)
    {
        $userId = Auth::id();

        $collaborations = EventCollaborator::with(['evenement.medias', 'evenement.organisateur'])
            ->where('organizer_id', $userId)
            ->where('statut', 'accepted')
            ->get();

        // Extract the events out of the collaborations to make it easier for the frontend
        $events = $collaborations->map(function ($collab) {
            return $collab->evenement;
        })->filter(); // filter out any nulls

        return response()->json(['data' => $events]);
    }
}
