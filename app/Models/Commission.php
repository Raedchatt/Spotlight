<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Enums\StatutCommission;

class Commission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'evenement_id',
        'reservation_id',
        'revendeur_id',
        'commission_organisateur',
        'commission_admin',
        'commission_revendeur',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => StatutCommission::class,
        ];
    }

    /**
     * Get the reseller that earned this commission.
     */
    public function revendeur(): BelongsTo
    {
        return $this->belongsTo(Revendeur::class);
    }

    /**
     * Get the reservation that this commission belongs to.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Get the event that this commission belongs to.
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    /**
     * Scope to filter commissions that are ready to be paid out by admin.
     * Ready means the event has finished at least 1 day ago.
     */
    public function scopeReadyForApproval($query)
    {
        return $query->whereHas('evenement', function ($q) {
            $q->where('datefin', '<', now()->subDay());
        });
    }

    /**
     * Boot the model.
     * Listens for status changes to Approved to increment the reseller's balance.
     * Also listens for transitions FROM Approved to decrement if needed (e.g. Reversed).
     */
    protected static function booted()
    {
        static::updated(function (Commission $commission) {
            if (!$commission->revendeur_id) return;

            // Check if status transitioned to Approved
            if ($commission->isDirty('status') && $commission->status === StatutCommission::Approved) {
                $commission->revendeur->increment('balance', $commission->commission_revendeur);
                
                \Log::info("Commission #{$commission->id} approved. Credited {$commission->commission_revendeur} to reseller ID {$commission->revendeur_id}");
            }

            // Check if status transitioned FROM Approved to something else (e.g. reversed)
            if ($commission->isDirty('status') && $commission->getOriginal('status') === StatutCommission::Approved && $commission->status !== StatutCommission::Approved) {
                $commission->revendeur->decrement('balance', $commission->commission_revendeur);

                \Log::info("Commission #{$commission->id} reverted from Approved. Debited {$commission->commission_revendeur} from reseller ID {$commission->revendeur_id}");
            }
        });

        static::created(function (Commission $commission) {
            if (!$commission->revendeur_id) return;

            // If created directly as Approved, increment the balance
            if ($commission->status === StatutCommission::Approved) {
                $commission->revendeur->increment('balance', $commission->commission_revendeur);
                
                \Log::info("Commission #{$commission->id} created as Approved. Credited {$commission->commission_revendeur} to reseller ID {$commission->revendeur_id}");
            }
        });
    }
}
