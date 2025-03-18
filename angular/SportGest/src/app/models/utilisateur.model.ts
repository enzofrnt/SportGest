import { Seance } from './seance.model';
import { FicheDePaie } from './fiche-de-paie.model'
import { NiveauSportif } from './enum/niveau-sportif.enum';

export interface Utilisateur {
    id?: number;
    nom: string;
    prenom: string;
    email: string;
    roles?: string[];
    token?: string;
}
