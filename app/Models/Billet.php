<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billet extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'codeQR',
        'dateEmission',
        'statut',
    ];

    // Relationship: Billet belongs to Reservation
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}