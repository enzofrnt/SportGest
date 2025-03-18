import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, map } from 'rxjs';
import { ApiService } from './api.service';
import { Utilisateur } from '../models/utilisateur.model';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private endpoint = 'auth';
  private currentUserSubject: BehaviorSubject<Utilisateur | null>;
  public currentUser$: Observable<Utilisateur | null>;

  constructor(
    private http: HttpClient,
    private apiService: ApiService
  ) {
    this.currentUserSubject = new BehaviorSubject<Utilisateur | null>(JSON.parse(localStorage.getItem('currentUser') || 'null'));
    this.currentUser$ = this.currentUserSubject.asObservable();
  }

  public get currentUserValue(): Utilisateur | null {
    return this.currentUserSubject.value;
  }

  async login(email: string, password: string): Promise<Observable<Utilisateur>> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return this.http.post<{token: string, user: Utilisateur}>(`${url}/login`, { email, password })
      .pipe(
        map(response => {
          const user: Utilisateur = {
            ...response.user,
            token: response.token
          };
          console.log(user);
          return user;
        }),
        tap(user => {
          localStorage.setItem('currentUser', JSON.stringify(user));
          this.currentUserSubject.next(user);
        })
      );
  }

  logout(): void {
    localStorage.removeItem('currentUser');
    this.currentUserSubject.next(null);
  }

  isAuthenticated(): boolean {
    return !!this.currentUserValue;
  }

  getToken(): string | null {
    return this.currentUserValue?.token || null;
  }
}
