<?php

namespace App\Models;

use App\Enums\StatutReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\TicketService;
use App\Services\CommissionService;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
     * The commission record associated with this reservation.
     */
    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    /**
     * Get the total price for this reservation.
     * 
     * @return float
     */
    public function getTotalPrice(): float
    {
        $evenement = $this->evenement;
        if (!$evenement) return 0.0;

        $unitPrice = (float) ($evenement->prix_spectateur ?? 0);

        if ($evenement->is_tournoi && $this->ticket_type === 'participant') {
            $unitPrice = (float) ($evenement->prix_participant ?? 0);
        }

        return $unitPrice * $this->nombre_tickets;
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
        $handleConfirmation = function (Reservation $reservation) {
            // Generate ticket if it doesn't already have one
            if ($reservation->billets()->count() === 0) {
                $ticketService = app(TicketService::class);
                $ticketService->generate($reservation);
            }

            // Credit commission to reseller if applicable
            if ($reservation->revendeur_id) {
                $reselleur = $reservation->reselleur;
                if ($reselleur) {
                    $commissionService = app(CommissionService::class);
                    $shares = $commissionService->calculate($reservation);
                    $commissionAmount = $shares['reseller_share'];

                    if ($commissionAmount > 0) {
                        $reselleur->increment('balance', $commissionAmount);
                        \Log::info("Commission of {$commissionAmount} credited to reseller ID {$reselleur->id} for reservation #{$reservation->id}");
                    }
                }
            }
        };

        static::created(function (Reservation $reservation) use ($handleConfirmation) {
            // 1. Record the commission breakdown for every new reservation
            $commissionService = app(CommissionService::class);
            $commissionService->createForReservation($reservation);

            // 2. Handle immediate confirmation (e.g. for free events)
            if ($reservation->statut === StatutReservation::Confirmed) {
                $handleConfirmation($reservation);
            }
        });

        static::updated(function (Reservation $reservation) use ($handleConfirmation) {
            // Handle status change to confirmed
            if ($reservation->isDirty('statut') && $reservation->statut === StatutReservation::Confirmed) {
                $handleConfirmation($reservation);
            }
        });
    }
}
