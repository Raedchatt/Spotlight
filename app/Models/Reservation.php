<?php

namespace App\Models;

use App\Enums\StatutReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\TicketService;

class Reservation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'evenement_id',
        'ticket_type',
        'statut',
        'nombre_tickets',
        'note',
        'revendeur_id',
    ];

    /**
     * Attribute casting — statut is always a StatutReservation enum instance.
     */
    protected function casts(): array
    {
        return [
            'statut'          => StatutReservation::class,
            'nombre_tickets'  => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who made this reservation (spectator).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The event that was reserved.
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    /**
     * The reseller associated with this reservation.
     */
    public function reselleur(): BelongsTo
    {
        return $this->belongsTo(Revendeur::class, 'revendeur_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Only reservations with status "pending".
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('statut', StatutReservation::Pending);
    }

    /**
     * Only reservations with status "confirmed".
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('statut', StatutReservation::Confirmed);
    }

    /**
     * Only reservations with status "cancelled".
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('statut', StatutReservation::Cancelled);
    }

    /**
     * Active reservations (pending OR confirmed) — useful for capacity checks.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('statut', [
            StatutReservation::Pending->value,
            StatutReservation::Confirmed->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Business Logic
    // -------------------------------------------------------------------------

    /**
     * A reservation can be cancelled as long as it is not already cancelled.
     * Organisers can also cancel confirmed reservations.
     */
    public function isCancellable(): bool
    {
        return in_array($this->statut, [
            StatutReservation::Pending,
            StatutReservation::Confirmed,
        ]);
    }

    /**
     * Confirm this reservation (transitions: pending → confirmed).
     */
    public function confirm(): bool
    {
        return $this->update(['statut' => StatutReservation::Confirmed]);
    }

    /**
     * Cancel this reservation (transitions: pending|confirmed → cancelled).
     */
    public function cancel(): bool
    {
        return $this->update(['statut' => StatutReservation::Cancelled]);
    }

    /**
     * The tickets (billets) associated with this reservation.
     */
    public function billets()
    {
        return $this->hasMany(Billet::class);
    }

    /**
     * The payments associated with this reservation.
     */
    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::updated(function (Reservation $reservation) {
            // Check if status changed to confirmed 
            if ($reservation->isDirty('statut') && $reservation->statut === StatutReservation::Confirmed) {
                // Generate ticket if it doesn't already have one
                if ($reservation->billets()->count() === 0) {
                    $ticketService = app(TicketService::class);
                    $ticketService->generate($reservation);
                }

                // Credit commission to reseller if applicable
                if ($reservation->revendeur_id) {
                    $reselleur = $reservation->reselleur;
                    if ($reselleur) {
                        // Determine correct price based on ticket type
                        $unitPrice = (float) $reservation->evenement->prix_spectateur;
                        if ($reservation->evenement->is_tournoi && $reservation->ticket_type === 'participant') {
                            $unitPrice = (float) $reservation->evenement->prix_participant;
                        }

                        $totalPrice = $unitPrice * $reservation->nombre_tickets;
                        $commission = $totalPrice * 0.05; // 5% reseller commission per ticket
                        $reselleur->increment('balance', $commission);
                        
                        \Log::info("Commission of {$commission} credited to reseller ID {$reselleur->id} for reservation #{$reservation->id}");
                    }
                }
            }
        });
        
        static::created(function (Reservation $reservation) {
            // Check if created with confirmed status right away (e.g. for free events)
            if ($reservation->statut === StatutReservation::Confirmed) {
                // Generate ticket
                $ticketService = app(TicketService::class);
                $ticketService->generate($reservation);
            }
        });
    }
}
