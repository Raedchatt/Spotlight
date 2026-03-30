<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'administrateur';
    case Organisateur = 'organisateur';
    case Participant = 'participant';
}
