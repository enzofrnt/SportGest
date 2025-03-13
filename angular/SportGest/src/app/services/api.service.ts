import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private apiUrl = 'http://localhost:8000/api'; // URL de base de l'API Symfony

  constructor() { }

  getApiUrl(): string {
    return this.apiUrl;
  }

  getEndpointUrl(endpoint: string): string {
    return `${this.apiUrl}/${endpoint}`;
  }
}
