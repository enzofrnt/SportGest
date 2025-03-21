import { Injectable } from '@angular/core';
import { API_URL } from '../../environments/environment';
import { firstValueFrom } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { bypassJwt } from '../interceptors/jwt.interceptor';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl: string = API_URL;
  private initializationPromise: Promise<void>;

  constructor(private http: HttpClient) {
    this.initializationPromise = this.initializeApiUrl();
  }

  private async initializeApiUrl(): Promise<void> {
    try {
      const config = await firstValueFrom(
        this.http.get<{ API_URL: string }>('assets/json/runtime.json', {
          context: bypassJwt()
        })
      );
      this.apiUrl = config.API_URL === '$API_URL' ? API_URL : config.API_URL;
      console.log('API_URL');
      console.log(this.apiUrl);
    } catch (error) {
      console.error('Failed to load API configuration:', error);
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
