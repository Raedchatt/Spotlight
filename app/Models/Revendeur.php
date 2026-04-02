<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revendeur extends Model
{
    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'referral_code',
        'balance',
    ];

    /**
     * Get the user account linked to this reseller profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a referral link for a specific event.
     * 
     * @param int|Evenement $event
     * @return string
     */
    public function getReferralLink($event): string
    {
        $eventId = $event instanceof Evenement ? $event->id : $event;
        return url("/events/{$eventId}?ref={$this->referral_code}");
    }
}
