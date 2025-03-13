import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Seance } from '../models/seance.model';

@Injectable({
  providedIn: 'root'
})
export class SeanceApiService {
  private endpoint = 'seances';

  constructor(
    private http: HttpClient,
    private apiService: ApiService
  ) { }

  getSeances(): Observable<Seance[]> {
    return this.http.get<Seance[]>(this.apiService.getEndpointUrl(this.endpoint));
  }

  getSeance(id: number): Observable<Seance> {
    return this.http.get<Seance>(`${this.apiService.getEndpointUrl(this.endpoint)}/${id}`);
  }

  createSeance(seance: Seance): Observable<Seance> {
    return this.http.post<Seance>(this.apiService.getEndpointUrl(this.endpoint), seance);
  }

  updateSeance(id: number, seance: Seance): Observable<Seance> {
    return this.http.put<Seance>(`${this.apiService.getEndpointUrl(this.endpoint)}/${id}`, seance);
  }

  deleteSeance(id: number): Observable<void> {
    return this.http.delete<void>(`${this.apiService.getEndpointUrl(this.endpoint)}/${id}`);
  }
}
