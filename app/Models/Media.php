<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Enums\TypeMedia;

class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'url',
        'type',
        'date_upload',
        'mediable_id',
        'mediable_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TypeMedia::class,
            'date_upload' => 'datetime',
        ];
    }

    /**
     * Get the parent mediable model (Evenement or Utilisateur).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
