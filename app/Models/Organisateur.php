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
        'stripe_account_id',
    ];

    /**
     * Appended attributes for JSON serialization.
     */
    protected $appends = ['has_stripe_account'];

    /**
     * Accessor for has_stripe_account.
     * Checks if a Stripe account is linked.
     */
    public function getHasStripeAccountAttribute(): bool
    {
        return !empty($this->stripe_account_id);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
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