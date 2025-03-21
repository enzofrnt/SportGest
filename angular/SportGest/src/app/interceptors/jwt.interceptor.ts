import { inject } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptorFn,
  HttpErrorResponse,
  HttpContext,
  HttpContextToken
} from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';

export const BYPASS_JWT = new HttpContextToken<boolean>(() => false);

export const bypassJwt = () => {
  return new HttpContext().set(BYPASS_JWT, true);
};

export const jwtInterceptor: HttpInterceptorFn = (req, next) => {
  // Vérifier si la requête doit être ignorée
  if (req.context.get(BYPASS_JWT)) {
    return next(req);
  }

  const authService = inject(AuthService);
  const token = authService.getToken();

  if (token) {
    req = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`
      }
    });
  }

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      // Liste des routes publiques où on ne veut pas déclencher de déconnexion automatique
      const publicRoutes = [
        '/api/coachs',
        '/api/seances'
      ];

      // Vérifier si l'URL de la requête contient une des routes publiques
      const isPublicRoute = publicRoutes.some(route => req.url.includes(route));

      // Ne déclencher la déconnexion que si ce n'est pas une route publique
      if (error.status === 401 && !isPublicRoute) {
        authService.logout();
      }

      return throwError(() => error);
    })
  );
};
