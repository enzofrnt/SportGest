import { Utilisateur } from './utilisateur.model';
import { NiveauSportif } from './enum/niveau-sportif.enum';
import { Seance } from './seance.model';

export interface Sportif extends Utilisateur {
  niveau: NiveauSportif;
  seances?: Seance[];
}