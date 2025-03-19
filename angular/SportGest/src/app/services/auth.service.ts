import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, map, catchError, throwError, firstValueFrom } from 'rxjs';
import { ApiService } from './api.service';
import { Utilisateur } from '../models/utilisateur.model';
import { Router } from '@angular/router';
import { jwtDecode } from 'jwt-decode';

interface TokenData {
  exp: number;
  iat: number;
  roles: string[];
  username: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private endpoint = 'auth';
  private currentUserSubject: BehaviorSubject<Utilisateur | null>;
  public currentUser$: Observable<Utilisateur | null>;
  private tokenExpirationTimer: any;
  private readonly USER_KEY = 'user_data';
  private readonly TOKEN_KEY = 'BEARER';

  constructor(
    private http: HttpClient,
    private apiService: ApiService,
    private router: Router
  ) {
    this.currentUserSubject = new BehaviorSubject<Utilisateur | null>(this.getUserFromStorage());
    this.currentUser$ = this.currentUserSubject.asObservable();
    this.checkAuthentication();
  }

  getToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  private getUserFromStorage(): Utilisateur | null {
    const userData = localStorage.getItem(this.USER_KEY);
    return userData ? JSON.parse(userData) : null;
  }

  private setUserInStorage(user: Utilisateur): void {
    localStorage.setItem(this.USER_KEY, JSON.stringify(user));
  }

  private clearStorage(): void {
    localStorage.removeItem(this.USER_KEY);
    localStorage.removeItem(this.TOKEN_KEY);
    if (this.tokenExpirationTimer) {
      clearTimeout(this.tokenExpirationTimer);
      this.tokenExpirationTimer = null;
    }
  }

  private handleError(error: HttpErrorResponse) {
    if (error.status === 401) {
      this.clearStorage();
      this.currentUserSubject.next(null);
      this.router.navigate(['/login']);
    }
    return throwError(() => error);
  }

  private async checkAuthentication(): Promise<void> {
    const userData = this.getUserFromStorage();
    if (!userData) {
      this.currentUserSubject.next(null);
      return;
    }

    try {
      const url = await this.apiService.getEndpointUrl(this.endpoint);
      this.http.get(`${url}/verify`, {
        withCredentials: true,
        headers: new HttpHeaders({
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }),
        observe: 'response'
      }).pipe(
        tap(() => console.log('Cookie BEARER envoyé:', document.cookie)),
        catchError((error) => {
          console.error('Authentication check error:', error);
          if (error.status === 401 || error.status === 0) {
            this.clearStorage();
            this.currentUserSubject.next(null);
          }
          return throwError(() => error);
        })
      ).subscribe({
        next: (response) => {
          if (response.status === 200) {
            this.currentUserSubject.next(userData);
          }
        },
        error: (error) => {
          console.error('Authentication check subscription error:', error);
          if (error.status === 401) {
            this.clearStorage();
            this.currentUserSubject.next(null);
          }
        }
      });
    } catch (error) {
      console.error('Error checking authentication:', error);
      this.clearStorage();
      this.currentUserSubject.next(null);
    }
  }

  public get currentUserValue(): Utilisateur | null {
    return this.currentUserSubject.value;
  }

  async login(email: string, password: string): Promise<Observable<Utilisateur>> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return this.http.post<{user: Utilisateur}>(`${url}/login`, { email, password }, {
      withCredentials: true
    }).pipe(
      map(response => response.user),
      tap(user => {
        this.setUserInStorage(user);
        this.currentUserSubject.next(user);
        console.log('Cookie BEARER stocké:', document.cookie);
      }),
      catchError(this.handleError.bind(this))
    );
  }

  async logout(): Promise<void> {
    if (!this.isAuthenticated()) {
      this.clearStorage();
      this.currentUserSubject.next(null);
      this.router.navigate(['/']);
      return;
    }

    try {
      const url = await this.apiService.getEndpointUrl(this.endpoint);
      this.http.post(`${url}/logout`, {}, {
        withCredentials: true
      }).pipe(
        catchError(error => {
          this.clearStorage();
          this.currentUserSubject.next(null);
          this.router.navigate(['/']);
          return throwError(() => error);
        })
      ).subscribe({
        next: () => {
          this.clearStorage();
          this.currentUserSubject.next(null);
          this.router.navigate(['/']);
        },
        error: () => {
          // Déjà géré dans le catchError
        }
      });
    } catch {
      this.clearStorage();
      this.currentUserSubject.next(null);
      this.router.navigate(['/']);
    }
  }

  isAuthenticated(): boolean {
    return !!this.currentUserValue;
  }

  // Méthode pour mettre à jour le profil utilisateur
  async updateUserProfile(userData: Partial<Utilisateur>): Promise<Utilisateur> {
    try {
      const url = await this.apiService.getEndpointUrl(this.endpoint);
      const observable = this.http.put<{message: string, user: Utilisateur}>(`${url}/user`, userData, {
        withCredentials: true
      }).pipe(
        map(response => response.user),
        tap(user => {
          // Mettre à jour l'utilisateur dans le stockage et dans le BehaviorSubject
          const currentUser = this.getUserFromStorage();
          if (currentUser) {
            const updatedUser = { ...currentUser, ...user };
            this.setUserInStorage(updatedUser);
            this.currentUserSubject.next(updatedUser);
          }
        }),
        catchError(this.handleError.bind(this))
      );
      
      // Utiliser firstValueFrom au lieu de toPromise
      return await firstValueFrom(observable);
    } catch (error) {
      console.error('Erreur lors de la mise à jour du profil:', error);
      throw error;
    }
  }
}
