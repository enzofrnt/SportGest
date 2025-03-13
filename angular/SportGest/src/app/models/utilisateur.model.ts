import { Seance } from './seance.model';
import { FicheDePaie } from './fiche-de-paie.model';
import { NiveauSportif } from './enum/niveau-sportif.enum';

export interface Utilisateur {
    id?: number;
    nom: string;
    prenom: string;
    email: string;
    password?: string;
    roles: string[];
}

export interface Coach extends Utilisateur {
    seances?: Seance[];
    fichesDePaie?: FicheDePaie[];
}

export interface Sportif extends Utilisateur {
    niveau: NiveauSportif;
    seances?: Seance[];
}

export interface Responsable extends Utilisateur {
    // Propriétés spécifiques au responsable si nécessaire
}
