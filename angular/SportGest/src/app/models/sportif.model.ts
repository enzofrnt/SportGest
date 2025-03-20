import { Utilisateur } from './utilisateur.model';
import { NiveauSportif } from './enum/niveau-sportif.enum';
import { Reservation } from './reservation.model';

export interface Sportif extends Utilisateur {
  niveau: NiveauSportif;
  reservations?: Reservation[];
}
