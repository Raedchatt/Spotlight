<?php

namespace App\Models;

use App\Enums\StatutReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Services\TicketService;
use App\Services\CommissionService;
use App\Models\AffiliateEarning;
use App\Models\Commission;
use App\Enums\StatutAffiliateEarning;

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

    /**
     * Get the earnings associated with this reservation.
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(AffiliateEarning::class);
    }

    /**
     * Get the commission breakdown associated with this reservation.
     */
    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
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
     * Credit commission to the associated reseller.
     * This initializes the earning record as PENDING.
     */
    public function creditCommission(): void
    {
        if (!$this->revendeur_id) {
            return;
        }

        $reselleur = $this->reselleur;
        if (!$reselleur) {
            return;
        }

        // Use CommissionService to get the correct calculation
        $commissionService = app(CommissionService::class);
        $shares = $commissionService->calculate($this);
        $commissionAmount = $shares['reseller_share'];
        
        if ($commissionAmount > 0) {
            // Record the earning as pending if it doesn't already exist
            $this->earnings()->firstOrCreate([
                'revendeur_id' => $reselleur->id,
            ], [
                'amount' => $commissionAmount,
                'status' => StatutAffiliateEarning::Pending,
            ]);

            \Log::info("Commission recorded as Pending for reseller ID {$reselleur->id} for reservation #{$this->id}");
        }
    }

    /**
     * Get the total price for this reservation.
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
            // Handle status change to confirmed
            if ($reservation->isDirty('statut') && $reservation->statut === StatutReservation::Confirmed) {
                // 1. Generate tickets
                if ($reservation->billets()->count() === 0) {
                    $ticketService = app(TicketService::class);
                    $ticketService->generate($reservation);
                }

                // 2. Approve affiliate earnings (triggering balance update)
                $reservation->earnings()
                    ->where('status', StatutAffiliateEarning::Pending)
                    ->get()
                    ->each(fn($earning) => $earning->update(['status' => StatutAffiliateEarning::Approved]));
            }

            // Handle status change to cancelled
            if ($reservation->isDirty('statut') && $reservation->statut === StatutReservation::Cancelled) {
                // Reverse any associated affiliate earnings
                $reservation->earnings()
                    ->whereIn('status', [StatutAffiliateEarning::Pending, StatutAffiliateEarning::Approved])
                    ->get()
                    ->each(fn($earning) => $earning->update(['status' => StatutAffiliateEarning::Reversed]));
            }
        });
        
        static::created(function (Reservation $reservation) {
            // 1. Create the general commission breakdown (log)
            $commissionService = app(CommissionService::class);
            $commissionService->createForReservation($reservation);

            // 2. Initialize affiliate earning if there's a reseller
            if ($reservation->revendeur_id) {
                $reservation->creditCommission();
                
                // If it was created as confirmed (e.g. free event), approve right away
                if ($reservation->statut === StatutReservation::Confirmed) {
                    $reservation->earnings()
                        ->where('status', StatutAffiliateEarning::Pending)
                        ->get()
                        ->each(fn($earning) => $earning->update(['status' => StatutAffiliateEarning::Approved]));
                }
            }

            // 3. Handle confirmed creation logic (tickets, etc.)
            if ($reservation->statut === StatutReservation::Confirmed) {
                if ($reservation->billets()->count() === 0) {
                    $ticketService = app(TicketService::class);
                    $ticketService->generate($reservation);
                }
            }
        });
    }
}
