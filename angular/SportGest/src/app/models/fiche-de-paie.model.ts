import { PeriodePaie } from './enum/periode-paie.enum';
import { Coach } from './utilisateur.model';

export interface FicheDePaie {
    id?: number;
    coach: Coach;
    periode: PeriodePaie;
    montantTotal: number;
}
