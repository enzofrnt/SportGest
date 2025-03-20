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
   * Crée une nouvelle réservation pour une séance
   * @param seanceId L'identifiant de la séance à réserver
   */
  async createReservation(seanceId: number): Promise<Observable<{ message: string; reservation: Reservation }>> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return this.http.post<{ message: string; reservation: Reservation }>(url, { seance_id: seanceId }, {
      withCredentials: true
    });
  }

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

  /**
   * Vérifie si une séance est déjà réservée par le sportif connecté
   * @param seanceId L'identifiant de la séance à vérifier
   */
  async checkReservation(seanceId: number): Promise<Observable<{ isReserved: boolean; reservation?: Reservation }>> {
    const url = await this.apiService.getEndpointUrl(`${this.endpoint}/check/${seanceId}`);
    return this.http.get<{ isReserved: boolean; reservation?: Reservation }>(url, {
      withCredentials: true
    });
  }
}
