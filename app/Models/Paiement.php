<?php

namespace App\Models;

use App\Enums\StatutPaiement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'reservation_id',
        'montant',
        'currency',                    // ← ajouté
        'stripe_payment_intent_id',    // ← ajouté
        'stripe_session_id',           // ← ajouté
        'payment_method',              // ← ajouté
        'statut',
        'transferred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'statut'         => StatutPaiement::class,
            'transferred_at' => 'datetime',
            'montant'        => 'decimal:2',  // ← ajouté pour cohérence avec decimal(10,2)
        ];
    }

    /**
     * Relationship: A payment belongs to a reservation.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    // ─── Helpers Stripe ───────────────────────────────────────────

    /**
     * Check if payment is successful.
     */
    public function isSucceeded(): bool
    {
        return $this->statut === StatutPaiement::Succeeded;
    }

    /**
     * Check if payment is still pending.
     */
    public function isPending(): bool
    {
        return $this->statut === StatutPaiement::Pending;
    }
}