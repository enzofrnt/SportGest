import { Utilisateur } from './utilisateur.model';
import { Seance } from './seance.model';
import { FicheDePaie } from './fiche-de-paie.model';
import { Specialite } from './specialite.model';

export interface Coach extends Utilisateur {
  seances?: Seance[];
  fichesDePaie?: FicheDePaie[];
  tarifHoraire: number;
  specialites: Specialite[];
}
