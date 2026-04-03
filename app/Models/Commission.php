<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'commission_organisateur',
        'commission_admin',
        'commission_revendeur',
    ];

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
}
