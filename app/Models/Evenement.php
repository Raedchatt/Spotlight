<?php

namespace App\Models;

use App\Enums\CategorieEvenement;
use App\Enums\StatutEvenement;
use App\Models\EventCollaborator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        'is_paid_out',
        'paid_out_at',
        'categorie',
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
            'is_paid_out' => 'boolean',
            'paid_out_at' => 'datetime',
            'categorie' => CategorieEvenement::class,
            'is_tournoi' => 'boolean',
            'type_tournoi' => 'string',
            'prix_participant' => 'decimal:2',
            'capacite_participant' => 'integer',
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


    // -------------------------------------------------------------------------
    // Business Logic
    // -------------------------------------------------------------------------

    /**
     * Returns true if the event still has open spectator spots.
     * Counts only active (pending + confirmed) reservations.
     */
    public function hasAvailableSpots(): bool
    {
        $taken = $this->reservations()
            ->whereIn('statut', ['pending', 'confirmed'])
            ->count();

        return $taken < $this->capacite_spectateur;
    }

    /**
     * Returns true if the given user has an active (non-cancelled) reservation
     * for this event.
     */
    public function isReservedBy(User $user): bool
    {
        return $this->reservations()
            ->where('user_id', $user->id)
            ->whereIn('statut', ['pending', 'confirmed'])
            ->exists();
    }

    /**
     * Returns true if the given user is the owner or an accepted co-organizer.
     * Optionally checks for a specific permission (can_edit, can_cancel, etc.).
     */
    public function isManagedBy(?int $userId, ?string $permission = null): bool
    {
        if (!$userId) return false;

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
        return $query->whereDate('date_debut', '<=', $date)
            ->whereDate('date_fin', '>=', $date);
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
    public function scopeParCategorie(Builder $query, CategorieEvenement|string $categorie): Builder
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

    /**
     * Méthode: fermerReservation
     */
    public function fermerReservation(): bool
    {
        return $this->update(['statut' => StatutEvenement::Ferme]);
    }
}