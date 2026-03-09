<?php

namespace App\Enums;

enum StatutPaiement: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Transferred = 'transferred';
}
