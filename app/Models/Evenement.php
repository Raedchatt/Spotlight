<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\StatutEvenement;
use App\Enums\CategorieEvenement;

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
        'categorie',
        'is_tournoi',
        'type_tournoi',
        'prix_participant',
        'capacite_participant'
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