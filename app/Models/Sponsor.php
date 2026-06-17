<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sponsor extends Model
{
    protected $table = 'sponsors';

    protected $fillable = [
        'nom',
        'logo',
    ];

    /**
     * Events that have this sponsor.
     */
    public function evenements(): BelongsToMany
    {
        return $this->belongsToMany(Evenement::class, 'event_sponsor', 'sponsor_id', 'evenement_id');
    }
}
