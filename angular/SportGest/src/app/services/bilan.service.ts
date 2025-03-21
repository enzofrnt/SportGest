import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class BilanService {
  private endpoint = 'bilans';

  constructor(private http: HttpClient,private apiService: ApiService) { }

  /**
   * Récupère le bilan d'un sportif sur une période donnée
   * @param sportifId - Identifiant du sportif
   * @param dateMin - Date de début de la période (optionnel)
   * @param dateMax - Date de fin de la période (optionnel)
   * @returns Observable du bilan du sportif
   */
  getBilanSportif(sportifId: number, dateMin?: string, dateMax?: string): Observable<any> {
    let url = `${this.apiService.getEndpointUrl(this.endpoint)}/${sportifId}`;

    // Ajout des paramètres de requête si spécifiés
    const params: any = {};
    if (dateMin) params.date_min = dateMin;
    if (dateMax) params.date_max = dateMax;

    // Ajouter les options pour envoyer les cookies d'authentification
    return this.http.get<any>(url, {
      params,
      withCredentials: true
    });
  }
}