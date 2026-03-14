<?php

namespace App\Services;

use App\Models\Notification;
use App\Enums\TypeNotification;
use App\Models\User;
use App\Enums\Role;

class NotificationService
{
    /**
     * Notify all participants about a new event.
     */
    public function notifieParticipantsNouvelEvenement(string $eventName, string $date, string $lieu)
    {
        $participantIds = User::where('role', Role::Participant)->pluck('id')->toArray();
        Notification::creerPourPlusieurs(
            $participantIds,
            TypeNotification::EVENEMENT_CREE,
            "New event: {$eventName} on {$date} at {$lieu}"
        );
    }

    /**
     * Notify all participants about an event update.
     */
    public function notifieParticipantsEvenementModifie(string $eventName)
    {
        $participantIds = User::where('role', Role::Participant)->pluck('id')->toArray();
        Notification::creerPourPlusieurs(
            $participantIds,
            TypeNotification::EVENEMENT_MODIFIE,
            "Event updated: {$eventName} — check the latest details"
        );
    }

    /**
     * Notify all participants about an event cancellation.
     */
    public function notifieParticipantsEvenementAnnule(string $eventName)
    {
        $participantIds = User::where('role', Role::Participant)->pluck('id')->toArray();
        Notification::creerPourPlusieurs(
            $participantIds,
            TypeNotification::EVENEMENT_SUPPRIME,
            "Event cancelled: {$eventName}"
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
}