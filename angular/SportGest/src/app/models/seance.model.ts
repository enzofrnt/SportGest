import { TypeSeance } from './enum/type-seance.enum';
import { StatutSeance } from './enum/statut-seance.enum';
import { NiveauSportif } from './enum/niveau-sportif.enum';
import { Coach, Sportif } from './utilisateur.model';

export interface Seance {
    id?: number;
    dateHeure: Date;
    typeSeance: TypeSeance;
    themeSeance: string;
    coach: Coach;
    sportifs: Sportif[];
    statut: StatutSeance;
    niveauSeance: NiveauSportif;
    exercices: Exercice[];
}

export interface Exercice {
    id?: number;
    nom: string;
    description: string;
    duree: number;
    repetitions: number;
    series: number;
}
