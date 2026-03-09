<?php

namespace App\Models;

use App\Enums\StatutOrganisateur;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisateur extends Model
{
    protected $table = 'organisateurs';

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'user_id',
        'nom_organisation',
        'description',
        'telephone',
        'adresse',
        'site_web',
        'logo',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user account linked to this organizer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All events created by this organizer.
     */
    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class, 'organisateur_id', 'user_id');
    }

}