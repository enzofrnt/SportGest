import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, firstValueFrom } from 'rxjs';
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

  async getSeances(): Promise<Seance[]> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return firstValueFrom(this.http.get<Seance[]>(url));
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
