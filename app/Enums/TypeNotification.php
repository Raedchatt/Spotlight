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
    case COLLABORATION_REJECTED = 'collaboration_rejected';
    case EVENEMENT_APPROUVE = 'evenement_approuve';
    case EVENEMENT_REJETE = 'evenement_rejete';
    case PAIEMENT_EFFECTUE = 'paiement_effectue';
}