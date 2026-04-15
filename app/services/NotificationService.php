<?php

namespace App\Services;

use App\Models\Notification;
use App\Enums\TypeNotification;
use App\Models\User;
use App\Enums\Role;

class NotificationService
{
    protected $expoPush;

    public function __construct(ExpoPushService $expoPush)
    {
        $this->expoPush = $expoPush;
    }

    /**
     * Notify all participants about a new event.
     */
    public function notifieParticipantsNouvelEvenement(string $eventName, string $date, string $lieu, ?int $eventId = null)
    {
        $participantIds = User::where('role', '=', Role::Participant, 'and')->pluck('id')->toArray();
        Notification::creerPourPlusieurs(
            $participantIds,
            TypeNotification::EVENEMENT_CREE,
            "New event: {$eventName} on {$date} at {$lieu}"
        );

        $this->expoPush->sendToAll(
            "🎉 New Event Available!",
            "{$eventName} on {$date} at {$lieu}",
            ['type' => 'new_event', 'evenement_id' => $eventId]
        );
    }

    /**
     * Notify all participants about an event update.
     */
    public function notifieParticipantsEvenementModifie(string $eventName, ?int $eventId = null)
    {
        $participantIds = User::where('role', '=', Role::Participant, 'and')->pluck('id')->toArray();
        Notification::creerPourPlusieurs(
            $participantIds,
            TypeNotification::EVENEMENT_MODIFIE,
            "Event updated: {$eventName} — check the latest details"
        );

        $this->expoPush->sendToAll(
            "📝 Event Updated",
            "{$eventName} has been updated. Tap to see the latest details.",
            ['type' => 'event_updated', 'evenement_id' => $eventId]
        );
    }

    /**
     * Notify all participants about an event cancellation.
     */
    public function notifieParticipantsEvenementAnnule(string $eventName, ?int $eventId = null)
    {
        $participantIds = User::where('role', '=', Role::Participant, 'and')->pluck('id')->toArray();
        Notification::creerPourPlusieurs(
            $participantIds,
            TypeNotification::EVENEMENT_SUPPRIME,
            "Event cancelled: {$eventName}"
        );

        $this->expoPush->sendToAll(
            "❌ Event Cancelled",
            "{$eventName} has been cancelled.",
            ['type' => 'event_cancelled', 'evenement_id' => $eventId]
        );
    }

    /**
     * Notify an organizer about a new reservation.
     */
    public function notifieOrganisateurNouvelleReservation(int $organisateurId, string $participantName, int $ticketCount, string $eventName)
    {
        Notification::creer(
            $organisateurId,
            TypeNotification::RESERVATION_CREE,
            "{$participantName} reserved {$ticketCount} ticket(s) for {$eventName}"
        );
    }

    /**
     * Notify an organizer about a reservation cancellation.
     */
    public function notifieOrganisateurReservationAnnulee(int $organisateurId, string $participantName, string $eventName)
    {
        Notification::creer(
            $organisateurId,
            TypeNotification::RESERVATION_ANNULEE,
            "{$participantName} cancelled their reservation for {$eventName}"
        );
    }

    /**
     * Send a collaboration invitation notification.
     */
    public function envoyerInvitationCollaboration(int $inviteeId, string $eventName, string $inviterName, int $eventId)
    {
        Notification::creer(
            $inviteeId,
            TypeNotification::INVITATION_COLLABORATION,
            "{$inviterName} invited you to co-organize: {$eventName}",
            ['evenement_id' => $eventId]
        );
    }

    /**
     * Notify event owner that a collaboration was accepted.
     */
    public function notifieCollaborationAcceptee(int $ownerId, string $collaboratorName, string $eventName)
    {
        Notification::creer(
            $ownerId,
            TypeNotification::COLLABORATION_ACCEPTEE,
            "{$collaboratorName} accepted your collaboration invite for: {$eventName}"
        );
    }

    /**
     * Notify event owner that a collaboration was rejected.
     */
    public function notifieCollaborationRejected(int $ownerId, string $collaboratorName, string $eventName)
    {
        Notification::creer(
            $ownerId,
            TypeNotification::COLLABORATION_REJECTED,
            "{$collaboratorName} declined your collaboration invite for: {$eventName}"
        );
    }

    /**
     * Notify an organizer that their event was approved.
     */
    public function notifieOrganisateurEvenementApprouve(int $organisateurId, string $eventName)
    {
        Notification::creer(
            $organisateurId,
            TypeNotification::EVENEMENT_APPROUVE,
            "Good news! Your event '{$eventName}' has been approved and is now public."
        );
    }

    /**
     * Notify an organizer that their event was rejected.
     */
    public function notifieOrganisateurEvenementRejete(int $organisateurId, string $eventName)
    {
        Notification::creer(
            $organisateurId,
            TypeNotification::EVENEMENT_REJETE,
            "Your event '{$eventName}' has been rejected by administration."
        );
    }

    /**
     * Notify an organizer that their payout was processed.
     */
    public function notifieOrganisateurPaiementEffectue(int $organisateurId, string $eventName, float $amount)
    {
        Notification::creer(
            $organisateurId,
            TypeNotification::PAIEMENT_EFFECTUE,
            "Your payout of {$amount} € for '{$eventName}' has been processed."
        );
    }

    /**
     * Get IDs of all platform administrators.
     */
    private function getAdminIds(): array
    {
        // Role::Admin is stored as 'administrateur' internally but mapped to enum
        return User::where('role', '=', Role::Admin, 'and')->pluck('id')->toArray();
    }

    /**
     * Notify Admins about a new event pending validation.
     */
    public function notifieAdminsEvenementEnAttente(string $eventName, string $organizerName)
    {
        Notification::creerPourPlusieurs(
            $this->getAdminIds(),
            TypeNotification::EVENEMENT_CREE,
            "Action required: '{$eventName}' was submitted by {$organizerName} and is awaiting validation."
        );
    }

    /**
     * Notify Admins about an event modification.
     */
    public function notifieAdminsEvenementModifie(string $eventName, string $organizerName)
    {
        Notification::creerPourPlusieurs(
            $this->getAdminIds(),
            TypeNotification::EVENEMENT_MODIFIE,
            "Event '{$eventName}' was modified by {$organizerName} (may require re-validation)."
        );
    }

    /**
     * Notify Admins about an event cancellation/deletion.
     */
    public function notifieAdminsEvenementSupprime(string $eventName, string $organizerName)
    {
        Notification::creerPourPlusieurs(
            $this->getAdminIds(),
            TypeNotification::EVENEMENT_SUPPRIME,
            "Event '{$eventName}' was cancelled/deleted by {$organizerName}."
        );
    }
}