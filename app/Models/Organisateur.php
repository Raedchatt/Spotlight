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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'rib' => 'encrypted',
        'rib_popup_seen' => 'boolean',
    ];

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
        'rib',
        'stripe_account_id',
        'rib_popup_seen',
    ];

    /**
     * Appended attributes for JSON serialization.
     */
    protected $appends = ['has_rib'];

    /**
     * Accessor for has_rib.
     * Checks if a RIB is stored without triggering decryption.
     */
    public function getHasRibAttribute(): bool
    {
        $hasRib = !empty($this->getRawOriginal('rib'));
        // \Illuminate\Support\Facades\Log::info("Accessor has_rib called for Org ID {$this->id}: " . ($hasRib ? 'TRUE' : 'FALSE'));
        return $hasRib;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'rib',
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
        return $this->hasMany(Evenement::class , 'organisateur_id', 'user_id');
    }

}