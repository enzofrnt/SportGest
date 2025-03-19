import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Sportif {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  niveauSportif: string;
  dateInscription: string;
}

export interface Seance {
  id: number;
  themeSeance: string;
  dateHeure: string;
  typeSeance: string;
  statut: string;
  coach: {
    id: number;
    nom: string;
    prenom: string;
  };
}

@Injectable({
  providedIn: 'root'
})
export class SportifService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  // Récupérer les informations d'un sportif
  getSportif(id: number): Observable<Sportif> {
    return this.http.get<Sportif>(`${this.apiUrl}/sportifs/${id}`);
  }

  // Récupérer la liste des séances réservées
  getSeances(id: number): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiUrl}/sportifs/${id}/seances`);
  }

  // Récupérer l'historique des entraînements
  getHistorique(id: number): Observable<Seance[]> {
    return this.http.get<Seance[]>(`${this.apiUrl}/sportifs/${id}/historique`);
  }
} 