import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Sportif } from '../models/sportif.model';
import { Seance } from '../models/seance.model';

@Injectable({
  providedIn: 'root'
})
export class SportifService {
  private endpoint = 'sportifs';

  constructor(private http: HttpClient, private apiService: ApiService) { }

  // Récupérer les informations d'un sportif
  getSportif(id: number): Observable<Sportif> {
    return this.http.get<Sportif>(`${this.apiService.getEndpointUrl(this.endpoint)}/${id}`);
  }

  // Récupérer la liste des séances réservées
  getSeances(id: number): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiService.getEndpointUrl(this.endpoint)}/${id}/seances`);
  }

  // Récupérer l'historique des entraînements
  getHistorique(id: number): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiService.getEndpointUrl(this.endpoint)}/${id}/historique`);
  }
}