<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasTranslations;

    protected $table = 'categories';

    protected $fillable = [
        'slug',
        'label',
        'is_default',
    ];

    public $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
