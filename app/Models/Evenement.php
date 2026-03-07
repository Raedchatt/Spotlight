<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'categorie'
    ];

    /**
     * Relation with User (Organisateur)
     */
    public function organisateur()
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }
}