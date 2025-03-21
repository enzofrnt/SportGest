import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';
import { map, take } from 'rxjs/operators';

export const sportifGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.currentUser$.pipe(
    take(1),
    map(user => {
      // Vérifie que l'utilisateur a uniquement le rôle ROLE_USER
      if (user?.roles?.length === 1 && user.roles.includes('ROLE_USER')) {
        return true;
      } else if (user?.roles?.length === 2 && user.roles.includes('ROLE_SPORTIF') && user.roles.includes('ROLE_USER')) {
        return true;
      }

      // Rediriger vers la page d'accueil si authentifié mais pas uniquement sportif
      // ou vers la page de connexion si non authentifié
      const redirectUrl = user ? '/' : '/connexion';
      return router.createUrlTree([redirectUrl]);
    })
  );
};
