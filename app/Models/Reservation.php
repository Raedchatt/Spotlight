<?php

namespace App\Models;

use App\Enums\StatutReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
