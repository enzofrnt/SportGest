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
      console.log('try');
      const config = await firstValueFrom(
        this.http.get<{ API_URL: string }>('assets/json/runtime.json')
      );
      console.log("config");
      console.log(config);
      console.log(config.API_URL);
      this.apiUrl = config.API_URL === '$API_URL' ? API_URL : config.API_URL;
      console.log('this.apiUrl choisi');
      console.log(this.apiUrl);
    } catch {
      console.log('catch');
      this.apiUrl = API_URL;
      console.log(this.apiUrl);
    }
  }

  async getApiUrl(): Promise<string> {
    console.log('getApiUrl');
    await this.initializationPromise;
    console.log('this.apiUrl in getApiUrl');
    console.log(this.apiUrl);
    return this.apiUrl;
  }

  async getEndpointUrl(endpoint: string): Promise<string> {
    const apiUrl = await this.getApiUrl();
    return `${apiUrl}/${endpoint}`;
  }
}
