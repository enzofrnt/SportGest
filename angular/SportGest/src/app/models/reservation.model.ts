import { Sportif } from './sportif.model';
import { Seance } from './seance.model';

export interface Reservation {
    id?: number;
    sportif: Sportif;
    seance: Seance;
    presence?: boolean;
}
