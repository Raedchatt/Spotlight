<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRecord extends Model
{
    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'paiement_id',
        'evenement_id',
        'type',
        'amount',
        'currency',
        'status',
        'stripe_reference',
        'description',
    ];

    /**
     * Relationship: A financial record may belong to a payment.
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    /**
     * Relationship: A financial record may belong to an event.
     */
    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }
}
