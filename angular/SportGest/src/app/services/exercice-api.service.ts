import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { firstValueFrom, Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Exercice } from '../models/exercice.model';

@Injectable({
  providedIn: 'root'
})
export class ExerciceApiService {
  private endpoint = 'exercices';

  constructor(
    private http: HttpClient,
    private apiService: ApiService
  ) { }

  /**
   * Récupère la liste de tous les exercices avec filtres optionnels
   */
  async getAllExercices(filters?: {
    difficulte?: string,
    duree_max?: number
  }): Promise<Observable<Exercice[]>> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);

    let params = new HttpParams();
    if (filters) {
      if (filters.difficulte) params = params.set('difficulte', filters.difficulte);
      if (filters.duree_max) params = params.set('duree_max', filters.duree_max.toString());
    }

    return this.http.get<Exercice[]>(url, { params });
  }

  /**
   * Récupère les détails d'un exercice spécifique
   */
  async getExerciceById(id: number): Promise<Exercice> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.get<Exercice>(`${url}/${id}`));
  }

  /**
   * Crée un nouvel exercice
   */
  async createExercice(exerciceData: {
    nom: string,
    description: string,
    dureeEstimee: number,
    difficulte: string
  }): Promise<{ message: string, id: number }> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.post<{ message: string, id: number }>(url, exerciceData));
  }

  /**
   * Met à jour un exercice existant
   */
  async updateExercice(id: number, exerciceData: Partial<{
    nom: string,
    description: string,
    dureeEstimee: number,
    difficulte: string
  }>): Promise<{ message: string }> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.put<{ message: string }>(`${url}/${id}`, exerciceData));
  }

  /**
   * Supprime un exercice
   */
  async deleteExercice(id: number): Promise<{ message: string }> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.delete<{ message: string }>(`${url}/${id}`));
  }
}
