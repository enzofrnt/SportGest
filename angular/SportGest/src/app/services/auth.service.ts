import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, map, catchError, throwError } from 'rxjs';
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

  constructor(
    private http: HttpClient,
    private apiService: ApiService,
    private router: Router
  ) {
    this.currentUserSubject = new BehaviorSubject<Utilisateur | null>(this.getUserFromStorage());
    this.currentUser$ = this.currentUserSubject.asObservable();
    this.checkAuthentication();
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
            this.router.navigate(['/login']);
          }
          return throwError(() => error);
        })
      ).subscribe({
        next: (response) => {
          if (response.status === 200) {
            const userData = this.getUserFromStorage();
            if (userData) {
              this.currentUserSubject.next(userData);
            }
          }
        },
        error: (error) => {
          console.error('Authentication check subscription error:', error);
          if (error.status === 401) {
            this.clearStorage();
            this.currentUserSubject.next(null);
            this.router.navigate(['/login']);
          }
        }
      });
    } catch (error) {
      console.error('Error checking authentication:', error);
      this.clearStorage();
      this.currentUserSubject.next(null);
      this.router.navigate(['/login']);
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
    try {
      const url = await this.apiService.getEndpointUrl(this.endpoint);
      this.http.post(`${url}/logout`, {}, {
        withCredentials: true
      }).pipe(
        catchError(this.handleError.bind(this))
      ).subscribe({
        next: () => {
          this.clearStorage();
          this.currentUserSubject.next(null);
          this.router.navigate(['/']);
        },
        error: () => {
          this.clearStorage();
          this.currentUserSubject.next(null);
          this.router.navigate(['/']);
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
}
