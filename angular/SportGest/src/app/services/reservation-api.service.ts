import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Reservation {
  id: number;
  seance: {
    id: number;
    themeSeance: string;
    [key: string]: any;
  };
}

@Injectable({
  providedIn: 'root'
})
export class ReservationService {
  private apiUrl = `${environment.apiUrl}/api/reservations`;

  constructor(private http: HttpClient) { }

  createReservation(seanceId: number): Observable<{ message: string; reservation: Reservation }> {
    return this.http.post<{ message: string; reservation: Reservation }>(this.apiUrl, { seance_id: seanceId });
  }

  deleteReservation(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.apiUrl}/${id}`);
  }

  getSportifReservations(sportifId: number): Observable<Reservation[]> {
    return this.http.get<Reservation[]>(`${this.apiUrl}/sportif/${sportifId}`);
  }
}