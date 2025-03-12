import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../services/auth.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const authService = inject(AuthService);
  
  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401) {
        // Déconnexion automatique si 401 Unauthorized est retourné par l'API
        authService.logout();
        router.navigate(['/connexion']);
      }
      
      const errorMessage = error.error?.message || error.statusText;
      return throwError(() => new Error(errorMessage));
    })
  );
}; 