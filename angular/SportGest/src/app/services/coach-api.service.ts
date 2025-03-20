import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { Coach } from '../models/coach.model';
import { Specialite } from '../models/specialite.model';
import { Seance } from '../models/seance.model';
import { ApiService } from './api.service';

@Injectable({
  providedIn: 'root'
})
export class CoachService {
  private endpoint = 'coachs';

  constructor(
    private http: HttpClient,
    private apiService: ApiService
  ) {}

  /**
   * Récupère la liste de tous les coachs
   */
  async getAllCoachs(): Promise<Observable<Coach[]>> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return this.http.get<Coach[]>(url);
  }

  /**
   * Récupère les détails d'un coach spécifique
   * @param id L'identifiant du coach
   */
  async getCoachById(id: number): Promise<Observable<Coach>> {
    const url = await this.apiService.getEndpointUrl(`${this.endpoint}/${id}`);
    return this.http.get<Coach>(url);
  }

  /**
   * Récupère les séances proposées par un coach
   * @param id L'identifiant du coach
   */
  async getCoachSeances(id: number): Promise<Observable<Seance[]>> {
    const url = await this.apiService.getEndpointUrl(`${this.endpoint}/${id}/seances`);
    return this.http.get<Seance[]>(url);
  }
}