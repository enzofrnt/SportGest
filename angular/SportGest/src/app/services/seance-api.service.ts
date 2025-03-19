import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
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

  async getSeances(filters?: {
    type_seance?: string,
    niveau_seance?: string,
    date_min?: string,
    date_max?: string,
    statut?: string
  }): Promise<Seance[]> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    
    let params = new HttpParams();
    if (filters) {
      if (filters.type_seance) params = params.set('type_seance', filters.type_seance);
      if (filters.niveau_seance) params = params.set('niveau_seance', filters.niveau_seance);
      if (filters.date_min) params = params.set('date_min', filters.date_min);
      if (filters.date_max) params = params.set('date_max', filters.date_max);
      if (filters.statut) params = params.set('statut', filters.statut);
    }

    return firstValueFrom(this.http.get<Seance[]>(url, { params }));
  }

  async getSeance(id: number): Promise<Seance> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.get<Seance>(`${url}/${id}`));
  }

  async createSeance(seance: Seance): Promise<Seance> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.post<Seance>(url, seance));
  }

  async updateSeance(id: number, seance: Seance): Promise<Seance> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.put<Seance>(`${url}/${id}`, seance));
  }

  async deleteSeance(id: number): Promise<void> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.delete<void>(`${url}/${id}`));
  }
}
