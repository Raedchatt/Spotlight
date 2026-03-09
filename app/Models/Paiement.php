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
            'statut' => StatutPaiement::class,
            'transferred_at' => 'datetime',
        ];
    }

    /**
     * Relationship: A payment belongs to a reservation.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
