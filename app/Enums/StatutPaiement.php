<?php

namespace App\Enums;

enum StatutPaiement: string
{
    case Pending   = 'pending';
    case Succeeded = 'succeeded';
    case Failed    = 'failed';
    case Canceled  = 'canceled';
    case Refunded  = 'refunded';
}