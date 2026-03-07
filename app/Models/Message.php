<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Message extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contenu',
        'contenu_original',
        'sender_id',
        'receiver_id',
        'lu',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lu' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope a query to only include unread messages.
     */
    public function scopeNonLus(Builder $query): Builder
    {
        return $query->where('lu', false);
    }

    /**
     * Scope a query to only include messages between two specific users.
     */
    public function scopeConversation(Builder $query, int $userId1, int $userId2): Builder
    {
        return $query->where(function (Builder $q) use ($userId1, $userId2) {
            $q->where(function (Builder $q2) use ($userId1, $userId2) {
                $q2->where('sender_id', $userId1)
                   ->where('receiver_id', $userId2);
            })->orWhere(function (Builder $q2) use ($userId1, $userId2) {
                $q2->where('sender_id', $userId2)
                   ->where('receiver_id', $userId1);
            });
        })->orderBy('created_at', 'asc');
    }

    /**
     * Determine if the message has been reformulated.
     */
    protected function estReformule(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->contenu !== $this->contenu_original,
        );
    }
}
