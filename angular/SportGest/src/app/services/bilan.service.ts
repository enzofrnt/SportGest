import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import { switchMap } from 'rxjs/operators';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class BilanService {
  private endpoint = 'bilans';

  constructor(private http: HttpClient, private apiService: ApiService) { }

  /**
   * Récupère le bilan d'un sportif sur une période donnée
   * @param sportifId - Identifiant du sportif
   * @param dateMin - Date de début de la période (optionnel)
   * @param dateMax - Date de fin de la période (optionnel)
   * @returns Observable du bilan du sportif
   */
  getBilanSportif(sportifId: number, dateMin?: string, dateMax?: string): Observable<any> {

    // Créer un Observable à partir de la Promise de l'URL
    return from(this.apiService.getEndpointUrl(this.endpoint)).pipe(
      switchMap(baseUrl => {
        const url = `${baseUrl}/${sportifId}`;
        // Ajout des paramètres de requête si spécifiés
        const params: any = {};
        if (dateMin) params.date_min = dateMin;
        if (dateMax) params.date_max = dateMax;

        return this.http.get<any>(url, { params });
      })
    );
  }
}