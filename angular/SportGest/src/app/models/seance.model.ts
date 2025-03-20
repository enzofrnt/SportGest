import { Coach } from './coach.model';
import { Exercice } from './exercice.model';
import { Specialite } from './specialite.model';
import { TypeSeance } from './enum/type-seance.enum';
import { StatutSeance } from './enum/statut-seance.enum';
import { NiveauSportif } from './enum/niveau-sportif.enum';
import { Reservation } from './reservation.model';

export interface Seance {
    id?: number;
    dateHeure: Date;
    typeSeance: TypeSeance;
    themeSeance: string;
    coach: Coach;
    reservations: Reservation[];
    statut: StatutSeance;
    niveauSeance: NiveauSportif;
    exercices: Exercice[];
    theme: Specialite;
}
