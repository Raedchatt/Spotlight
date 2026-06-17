<?php

namespace App\Models;

use App\Enums\StatutEvenement;
use App\Models\EventCollaborator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Evenement extends Model
{
    protected $table = 'evenements';

    protected $fillable = [
        'organisateur_id',
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'lieu',
        'prix_spectateur',
        'capacite_spectateur',
        'statut',
        'is_reminder_sent',
        'is_paid_out',
        'paid_out_at',
        'categorie',
        'categorie_autre',
        'sponsors_pending',
        'category_id',
        'is_tournoi',
        'type_tournoi',
        'prix_participant',
        'capacite_participant',
        'titre_image',
        'poster_url',
        'poster_public_id',
        'video_url',
        'video_public_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'prix_spectateur' => 'double',
            'capacite_spectateur' => 'integer',
            'statut' => StatutEvenement::class,
            'is_reminder_sent' => 'boolean',
            'is_paid_out' => 'boolean',
            'paid_out_at' => 'datetime',
            'is_tournoi' => 'boolean',
            'type_tournoi' => 'string',
            'prix_participant' => 'decimal:2',
            'capacite_participant' => 'integer',
            'sponsors_pending' => 'array',
        ];
    }

    /**
     * Relation with User (Organisateur)
     */
    public function organisateur()
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }

    /**
     * Relation with Media (Polymorphic)
     */
    public function medias()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * All reservations made for this event.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'evenement_id');
    }

    /**
     * All collaborators invited to co-organize this event.
     */
    public function collaborateurs(): HasMany
    {
        return $this->hasMany(EventCollaborator::class, 'evenement_id');
    }

    /**
     * Detailed tournament information.
     */
    public function tournoi(): HasOne
    {
        return $this->hasOne(Tournoi::class, 'evenement_id');
    }

    /**
     * Relation with Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Sponsors for this event.
     */
    public function sponsors(): BelongsToMany
    {
        return $this->belongsToMany(Sponsor::class, 'event_sponsor', 'evenement_id', 'sponsor_id');
    }


    // -------------------------------------------------------------------------
    // Business Logic
    // -------------------------------------------------------------------------

    /**
     * Returns true if the event still has open spectator spots.
     * Counts only active (pending + confirmed) reservations.
     */
    public function hasAvailableSpots(): bool
    {
        if (!$this->is_tournoi) {
            $taken = (int) $this->reservations()
                ->whereIn('statut', ['pending', 'confirmed'])
                ->sum('nombre_tickets');

            return $taken < $this->capacite_spectateur;
        }

        // For tournaments, check if either spectators OR participants have room
        // (This is a generic check, specific controllers handle type-specific logic)
        $spectatorReserved = (int) $this->reservations()
            ->active()
            ->where('ticket_type', 'spectator')
            ->sum('nombre_tickets');
        
        if ($spectatorReserved < $this->capacite_spectateur) {
            return true;
        }

        $participantReserved = (int) $this->reservations()
            ->active()
            ->where('ticket_type', 'participant')
            ->sum('nombre_tickets');

        $participantCapacity = ($this->type_tournoi === 'equipe' && $this->tournoi)
            ? ($this->tournoi->nombre_equipes * $this->tournoi->joueurs_par_equipe)
            : $this->capacite_participant;

        return $participantReserved < $participantCapacity;
    }

    /**
     * Returns true if the given user has an active (non-cancelled) reservation
     * for this event.
     */
    public function isReservedBy(User $user): bool
    {
        return $this->reservations()
            ->where('user_id', $user->id)
            ->whereIn('statut', ['confirmed'])
            ->exists();
    }

    /**
     * Returns true if the given user is the owner or an accepted co-organizer.
     * Optionally checks for a specific permission (can_edit, can_cancel, etc.).
     */
    public function isManagedBy(?int $userId, ?string $permission = null): bool
    {
        if (!$userId)
            return false;

        $user = User::find($userId);
        if ($user && $user->role === \App\Enums\Role::Admin) {
            return true;
        }

        if ($this->organisateur_id === $userId) {
            return true;
        }

        $query = $this->collaborateurs()
            ->where('organizer_id', $userId)
            ->where('statut', 'accepted');

        if ($permission) {
            $query->where($permission, true);
        }

        return $query->exists();
    }

    /**
     * Scope: chercherEvenementParOrganisateur
     */
    public function scopeParOrganisateur(Builder $query, int $organisateurId): Builder
    {
        return $query->where('organisateur_id', $organisateurId);
    }

    /**
     * Scope: chercherEvenementParTitre
     */
    public function scopeParTitre(Builder $query, string $titre): Builder
    {
        return $query->where('titre', 'like', '%' . $titre . '%');
    }

    /**
     * Scope: chercherEvenementParDate
     */
    public function scopeParDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('date_debut', '>=', $date);
    }

    /**
     * Scope: chercherEvenementParPrix
     */
    public function scopeParPrix(Builder $query, float $prixMax): Builder
    {
        return $query->where('prix_spectateur', '<=', $prixMax);
    }

    /**
     * Scope: chercherEvenementParCategorie
     */
    public function scopeParCategorie(Builder $query, string $categorie): Builder
    {
        return $query->where('categorie', $categorie);
    }

    /**
     * Scope: chercherEvenementParStatut
     */
    public function scopeParStatut(Builder $query, $statut): Builder
    {
        if (is_string($statut)) {
            $statuses = explode(',', $statut);
        } else {
            $statuses = (array) $statut;
        }

        return $query->whereIn('statut', $statuses);
    }

    /**
     * Méthode: ouvrirReservation
     */
    public function ouvrirReservation(): bool
    {
        return $this->update(['statut' => StatutEvenement::Ouvert]);
    }

    public function fermerReservation(): bool
    {
        return $this->update(['statut' => StatutEvenement::Ferme]);
    }

    /**
     * Override toArray to include sponsors_pending in the sponsors array.
     */
    public function toArray()
    {
        $array = parent::toArray();
        if (array_key_exists('sponsors', $array)) {
            $merged = collect($array['sponsors']);
            if (!empty($this->sponsors_pending)) {
                foreach ($this->sponsors_pending as $pending) {
                    if (isset($pending['type']) && $pending['type'] === 'existing') {
                        if (!$merged->contains('id', $pending['id'])) {
                            $merged->push([
                                'id' => $pending['id'],
                                'nom' => $pending['nom'] ?? 'Sponsor',
                                'logo' => $pending['logo'] ?? null,
                            ]);
                        }
                    } else {
                        $merged->push([
                            'id' => 'pending_' . uniqid(),
                            'nom' => $pending['nom'] ?? 'Sponsor',
                            'logo' => $pending['logo'] ?? null,
                        ]);
                    }
                }
            }
            $array['sponsors'] = $merged->values()->all();
        }
        return $array;
    }
}