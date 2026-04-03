<?php

namespace App\Enums;

enum StatutCommission: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Approuvé',
            self::Reversed => 'Annulé',
        };
    }
}
