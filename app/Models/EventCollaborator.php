<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventCollaborator extends Model
{
    protected $table = 'event_collaborators';

    protected $fillable = [
        'evenement_id',
        'organizer_id',
        'statut',
        'can_edit',
        'can_cancel',
        'can_manage_team',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
        'can_cancel' => 'boolean',
        'can_manage_team' => 'boolean',
    ];

    /**
     * The event this collaboration belongs to.
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class, 'evenement_id');
    }

    /**
     * The organizer (user) who was invited.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }
}
