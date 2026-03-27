<?php

namespace App\Enums;

enum StatutPaiement: string
{
    case Pending     = 'pending';
    case Succeeded   = 'succeeded';
    case Transferred = 'transferred'; // Added for admin transfers
    case Failed      = 'failed';
    case Canceled    = 'canceled';
    case Refunded    = 'refunded';
}