<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\StatutAffiliateEarning;

class AffiliateEarning extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'revendeur_id',
        'reservation_id',
        'amount',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => StatutAffiliateEarning::class,
        ];
    }

    /**
     * The reseller that earned this commission.
     */
    public function revendeur(): BelongsTo
    {
        return $this->belongsTo(Revendeur::class);
    }

    /**
     * The reservation linked to this commission.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Boot the model.
     * Listens for status changes to Approved to increment the reseller's balance.
     * Also listens for transitions FROM Approved to decrement if needed (e.g. Reversed).
     */
    protected static function booted()
    {
        static::updated(function (AffiliateEarning $earning) {
            // Check if status transitioned to Approved
            if ($earning->isDirty('status') && $earning->status === StatutAffiliateEarning::Approved) {
                $earning->revendeur->increment('balance', $earning->amount);
                
                \Log::info("Earning #{$earning->id} approved. Credited {$earning->amount} to reseller ID {$earning->revendeur_id}");
            }

            // Check if status transitioned FROM Approved to something else (e.g. reversed)
            if ($earning->isDirty('status') && $earning->getOriginal('status') === StatutAffiliateEarning::Approved && $earning->status !== StatutAffiliateEarning::Approved) {
                $earning->revendeur->decrement('balance', $earning->amount);

                \Log::info("Earning #{$earning->id} reverted from Approved. Debited {$earning->amount} from reseller ID {$earning->revendeur_id}");
            }
        });

        static::created(function (AffiliateEarning $earning) {
            // If created directly as Approved, increment the balance
            if ($earning->status === StatutAffiliateEarning::Approved) {
                $earning->revendeur->increment('balance', $earning->amount);
                
                \Log::info("Earning #{$earning->id} created as Approved. Credited {$earning->amount} to reseller ID {$earning->revendeur_id}");
            }
        });
    }
}
