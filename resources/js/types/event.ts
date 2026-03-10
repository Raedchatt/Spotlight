export type StatutEvenement = 'ouvert' | 'ferme' | 'encours' | 'en_attente' | 'annule' | 'valide';

export type CategorieEvenement = 'sportifs' | 'culturels' | 'scientifiques' | 'musicaux' | 'commerciaux';

export interface User {
    id: number;
    name: string;
    email: string;
    // Add other fields as needed
}

export interface Media {
    id: number;
    url: string;
    type: 'image' | 'video';
    date_upload: string;
}

export interface Tournoi {
    id: number;
    prix_participant: number;
    capacite_participant: number;
    type_tournoi: 'equipe' | 'individuel';
}

export interface Evenement {
    id: number;
    organisateur_id: number;
    titre: string;
    description: string;
    date_debut: string;
    date_fin: string;
    lieu: string;
    prix_spectateur: number;
    capacite_spectateur: number;
    statut: StatutEvenement;
    categorie: CategorieEvenement;
    is_tournoi?: boolean;
    type_tournoi?: string;
    prix_participant?: number;
    capacite_participant?: number;
    organisateur?: User;
    medias?: Media[];
    tournoi?: Tournoi;
    reservations_count?: number;
    created_at: string;
    updated_at: string;
}
