import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Reservation } from '../models/reservation.model';

@Injectable({
  providedIn: 'root'
})
export class ReservationService {
  private endpoint = 'reservations';

  constructor(
    private http: HttpClient,
    private apiService: ApiService
  ) {}

  /**
   * Annule une réservation existante
   * @param id L'identifiant de la réservation à annuler
   */
  async cancelReservation(id: number): Promise<Observable<{ message: string }>> {
    const url = await this.apiService.getEndpointUrl(`${this.endpoint}/${id}`);
    return this.http.delete<{ message: string }>(url, {
      withCredentials: true
    });
  }

  /**
   * Récupère toutes les réservations d'un sportif
   * @param sportifId L'identifiant du sportif
   */
  async getSportifReservations(sportifId: number): Promise<Observable<Reservation[]>> {
    const url = await this.apiService.getEndpointUrl(`${this.endpoint}/sportif/${sportifId}`);
    return this.http.get<Reservation[]>(url, {
      withCredentials: true
    });
  }
}
