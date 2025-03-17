import { Injectable } from '@angular/core';
import { API_URL } from '../../environments/environment';
import { firstValueFrom } from 'rxjs';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl!: string;
  private initializationPromise: Promise<void>;

  constructor(private http: HttpClient) {
    this.initializationPromise = this.initializeApiUrl();
  }

  private async initializeApiUrl(): Promise<void> {
    try {
      const config = await firstValueFrom(
        this.http.get<{ API_URL: string }>('assets/json/runtime.json')
      );
      this.apiUrl = config.API_URL === '$API_URL' ? API_URL : config.API_URL;
    } catch {
      this.apiUrl = API_URL;
    }
  }

  async getApiUrl(): Promise<string> {
    await this.initializationPromise;
    return this.apiUrl;
  }

  async getEndpointUrl(endpoint: string): Promise<string> {
    const apiUrl = await this.getApiUrl();
    return `${apiUrl}/${endpoint}`;
  }
}
