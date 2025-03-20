import { Coach } from './coach.model';
import { Sportif } from './sportif.model';
import { Exercice } from './exercice.model';
import { Specialite } from './specialite.model';
import { TypeSeance } from './enum/type-seance.enum';
import { StatutSeance } from './enum/statut-seance.enum';
import { NiveauSportif } from './enum/niveau-sportif.enum';

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
    theme: Specialite;
}
