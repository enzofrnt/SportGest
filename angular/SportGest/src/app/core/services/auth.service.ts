import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';
import { User } from '../models/user.model';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';

interface AuthResponse {
  token: string;
  user: User;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  private tokenSubject = new BehaviorSubject<string | null>(null);
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);

  public currentUser$ = this.currentUserSubject.asObservable();
  public token$ = this.tokenSubject.asObservable();
  public isAuthenticated$ = this.isAuthenticatedSubject.asObservable();

  private readonly TOKEN_KEY = 'auth_token';
  private readonly USER_KEY = 'current_user';
  private tokenExpirationTimer: any;

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    this.loadStoredAuthData();
  }

  register(email: string, password: string, firstname: string, lastname: string, level: string): Observable<User> {
    return this.http.post<AuthResponse>(`${environment.apiUrl}/register`, {
      email,
      password,
      firstname,
      lastname,
      level
    }).pipe(
      tap(response => {
        this.setAuthData(response);
      }),
      map(response => response.user)
    );
  }

  login(email: string, password: string): Observable<User> {
    return this.http.post<AuthResponse>(`${environment.apiUrl}/login`, {
      email,
      password
    }).pipe(
      tap(response => {
        this.setAuthData(response);
      }),
      map(response => response.user)
    );
  }

  logout(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
    this.currentUserSubject.next(null);
    this.tokenSubject.next(null);
    this.isAuthenticatedSubject.next(false);
    if (this.tokenExpirationTimer) {
      clearTimeout(this.tokenExpirationTimer);
    }
    this.router.navigate(['/']);
  }

  getToken(): string | null {
    return this.tokenSubject.value;
  }

  isAuthenticated(): boolean {
    return this.isAuthenticatedSubject.value;
  }

  private setAuthData(authResponse: AuthResponse): void {
    localStorage.setItem(this.TOKEN_KEY, authResponse.token);
    localStorage.setItem(this.USER_KEY, JSON.stringify(authResponse.user));
    this.currentUserSubject.next(authResponse.user);
    this.tokenSubject.next(authResponse.token);
    this.isAuthenticatedSubject.next(true);
    this.autoLogout(this.getTokenExpiration(authResponse.token));
  }

  private loadStoredAuthData(): void {
    const token = localStorage.getItem(this.TOKEN_KEY);
    const userJson = localStorage.getItem(this.USER_KEY);

    if (token && userJson) {
      const user = JSON.parse(userJson) as User;
      this.currentUserSubject.next(user);
      this.tokenSubject.next(token);
      this.isAuthenticatedSubject.next(true);

      // Vérifier si le token est expiré
      const expirationDuration = this.getTokenExpiration(token);
      if (expirationDuration > 0) {
        this.autoLogout(expirationDuration);
      } else {
        this.logout();
      }
    }
  }

  private getTokenExpiration(token: string): number {
    try {
      const payload = JSON.parse(atob(token.split('.')[1]));
      const expirationTimestamp = payload.exp * 1000; // convertir en millisecondes
      return expirationTimestamp - Date.now();
    } catch (error) {
      return 0;
    }
  }

  private autoLogout(expirationDuration: number): void {
    if (this.tokenExpirationTimer) {
      clearTimeout(this.tokenExpirationTimer);
    }
    this.tokenExpirationTimer = setTimeout(() => {
      this.logout();
    }, expirationDuration);
  }
}
