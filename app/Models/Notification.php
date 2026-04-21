<?php

namespace App\Models;

use App\Enums\TypeNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'type',
        'date_envoi',
        'lu',
        'data',
    ];

    protected $casts = [
        'type'       => TypeNotification::class,
        'date_envoi' => 'datetime',
        'lu'         => 'boolean',
        'data'       => 'array',
    ];

    /**
     * The user who receives this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marquer une notification comme lue
     */
    public function marquerCommeLue()
    {
        $this->lu = true;
        $this->save();
    }

    /**
     * Marquer toutes les notifications d'un utilisateur comme lues
     */
    public static function marquerTousCommeLue(?int $userId = null)
    {
        $query = self::where('lu', false);
        if ($userId) {
            $query->where('user_id', $userId);
        }
        $query->update(['lu' => true]);
    }

    /**
     * Create a notification for a specific user.
     */
    public static function creer(int $userId, TypeNotification $type, string $message, array $data = []): self
    {
        $notification = self::create([
            'user_id'    => $userId,
            'type'       => $type,
            'message'    => $message,
            'date_envoi' => now(),
            'lu'         => false,
            'data'       => !empty($data) ? $data : null,
        ]);

        try {
            broadcast(new \App\Events\NotificationSent($notification));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast failed: ' . $e->getMessage());
        }

        return $notification;
    }

    /**
     * Create notifications for multiple users.
     */
    public static function creerPourPlusieurs(array $userIds, TypeNotification $type, string $message): void
    {
        foreach ($userIds as $userId) {
            self::creer($userId, $type, $message);
        }
    }
}