import { Injectable } from '@angular/core';
import { API_URL } from '../../environments/environment';
import { firstValueFrom } from 'rxjs';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl!: string;

  constructor(private http: HttpClient) {
    this.initializeApiUrl();
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

  getApiUrl(): string {
    return this.apiUrl;
  }

  getEndpointUrl(endpoint: string): string {
    return `${this.apiUrl}/${endpoint}`;
  }
}
