<?php

namespace App\Enums;

enum TypeNotification: string
{
    case EVENEMENT_CREE = 'evenement_cree';
    case EVENEMENT_MODIFIE = 'evenement_modifie';
    case EVENEMENT_SUPPRIME = 'evenement_supprime';
    case RESERVATION_CREE = 'reservation_cree';
    case RESERVATION_ANNULEE = 'reservation_annulee';
    case INVITATION_COLLABORATION = 'invitation_collaboration';
    case COLLABORATION_ACCEPTEE = 'collaboration_acceptee';
}