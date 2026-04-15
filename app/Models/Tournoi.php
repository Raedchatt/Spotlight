<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\TypeTournoi;

class Tournoi extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'evenement_id',
        'prix_participant',
        'capacite_participant',
        'type_tournoi',
        'nombre_equipes',
        'joueurs_par_equipe',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prix_participant' => 'double',
            'capacite_participant' => 'integer',
            'type_tournoi' => TypeTournoi::class,
            'nombre_equipes' => 'integer',
            'joueurs_par_equipe' => 'integer',
        ];
    }

    /**
     * Get the base evenement.
     */
    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class);
    }

    /**
     * Get the participants of the tournoi.
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournoi_participants')
                    ->withPivot(['date_inscription', 'statut'])
                    ->withTimestamps();
    }

    /**
     * Get all of the tournoi's medias.
     */
    public function medias(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Scope a query to only include individuel tournois.
     */
    public function scopeIndividuel(Builder $query): Builder
    {
        return $query->where('type_tournoi', TypeTournoi::Individuel);
    }

    /**
     * Scope a query to only include equipe tournois.
     */
    public function scopeEquipe(Builder $query): Builder
    {
        return $query->where('type_tournoi', TypeTournoi::Equipe);
    }

    /**
     * Determine if the tournoi is full.
     */
    protected function isFull(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->participants()->count() >= $this->capacite_participant,
        );
    }
}
