<?php

namespace App\Enums;

enum StatutEvenement: string
{
    case Ouvert = 'ouvert';
    case Ferme = 'ferme';
    case EnCours = 'encours';
    case EnAttente = 'en_attente';
    case Annule = 'annule';
    case Valide = 'valide';
    case Rejete = 'rejete';
}
