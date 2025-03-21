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
  private readonly TOKEN_KEY = 'jwt_token';

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

  private setToken(token: string): void {
    localStorage.setItem(this.TOKEN_KEY, token);
  }

  private getUserFromStorage(): Utilisateur | null {
    const userData = localStorage.getItem(this.USER_KEY);
    if (!userData) return null;

    const user = JSON.parse(userData);
    // S'assurer que les rôles sont un tableau
    if (!user.roles || !Array.isArray(user.roles)) {
      user.roles = ['ROLE_USER', 'ROLE_SPORTIF'];
    }
    return user;
  }

  private setUserInStorage(user: Utilisateur): void {
    // S'assurer que les rôles sont un tableau
    if (user && (!user.roles || !Array.isArray(user.roles))) {
      user.roles = ['ROLE_USER', 'ROLE_SPORTIF'];
    }
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
      // this.router.navigate(['/login']);
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
        headers: new HttpHeaders({
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.getToken()}`
        }),
        observe: 'response'
      }).pipe(
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
    return this.http.post<{user: Utilisateur, token: string}>(`${url}/login`, { email, password }).pipe(
      map(response => {
        if (!response.token) {
          console.error('Pas de token dans la réponse');
          throw new Error('Token manquant dans la réponse');
        }
        this.setToken(response.token);

        // S'assurer que les rôles sont un tableau
        const user = response.user;
        if (!user.roles || !Array.isArray(user.roles)) {
          user.roles = ['ROLE_USER', 'ROLE_SPORTIF'];
        }

        return user;
      }),
      tap(user => {
        this.setUserInStorage(user);
        this.currentUserSubject.next(user);
      }),
      catchError(this.handleError.bind(this))
    );
  }

  async register(email: string, password: string, nom: string, prenom: string): Promise<Observable<Utilisateur>> {
    const url = await this.apiService.getEndpointUrl(this.endpoint);
    return this.http.post<{user: Utilisateur, token: string}>(`${url}/register`, {
      email,
      password,
      nom,
      prenom
    }).pipe(
      map(response => {
        if (!response.token) {
          throw new Error('Token manquant dans la réponse');
        }
        this.setToken(response.token);

        // S'assurer que les rôles sont un tableau
        const user = response.user;
        if (!user.roles || !Array.isArray(user.roles)) {
          user.roles = ['ROLE_USER', 'ROLE_SPORTIF'];
        }

        return user;
      }),
      tap(user => {
        this.setUserInStorage(user);
        this.currentUserSubject.next(user);
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
        headers: new HttpHeaders({
          'Authorization': `Bearer ${this.getToken()}`
        })
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
    return !!this.currentUserValue && !!this.getToken();
  }

  // Vérifie si l'utilisateur actuel est un sportif
  isSportif(): boolean {
    const user = this.currentUserValue;
    if (!user) return false;

    // Si l'utilisateur n'a pas de rôles, on considère qu'il est un sportif par défaut
    if (!user.roles || !Array.isArray(user.roles)) {
      return true;
    }

    // Un utilisateur est considéré comme sportif s'il a le rôle ROLE_SPORTIF ou ROLE_USER
    // et qu'il n'a pas les rôles ROLE_COACH ou ROLE_RESPONSABLE ou ROLE_ADMIN
    return (user.roles.includes('ROLE_SPORTIF') || user.roles.includes('ROLE_USER')) &&
           !user.roles.includes('ROLE_COACH') &&
           !user.roles.includes('ROLE_RESPONSABLE') &&
           !user.roles.includes('ROLE_ADMIN');
  }

  // Méthode pour mettre à jour le profil utilisateur
  async updateUserProfile(userData: Partial<Utilisateur>): Promise<Utilisateur> {
    try {
      const url = await this.apiService.getEndpointUrl(this.endpoint);
      const observable = this.http.put<{message: string, user: Utilisateur}>(`${url}/user`, userData, {
        headers: new HttpHeaders({
          'Authorization': `Bearer ${this.getToken()}`
        })
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
