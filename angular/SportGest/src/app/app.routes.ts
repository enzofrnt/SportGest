import { Routes } from '@angular/router';
import { LoginComponent } from './auth/login/login.component';
import { RegisterComponent } from './auth/register/register.component';
import { HomeComponent } from './features/home/home.component';
import { CoachListComponent } from './features/coach/coache-list/coach-list.component';
import { SeanceListComponent } from './features/seance/seance-list/seance-list.component';
import { SeancePlanningComponent } from './features/seance/seance-planning/seance-planning.component';
import { authGuard } from './guards/auth.guard';
import { ReservationListComponent } from './features/reservation/reservation-list/reservation-list.component';
import { BilanComponent } from './features/bilan/bilan.component';
import { ProfileComponent } from './features/profile/profile.component';
import { sportifGuard } from './guards/sportif.guard';

export const routes: Routes = [
  // Route principale - redirige vers la racine
  { path: '', component: HomeComponent },

  // Routes publiques
  { path: 'coachs', component: CoachListComponent },
  {
    path: 'coachs/:id',
    loadComponent: () => import('./features/coach/coach-details/coach-details.component').then(m => m.CoachDetailsComponent),
    title: 'Détails du coach'
  },

  { path: 'inscription', component: RegisterComponent },
  { path: 'connexion', component: LoginComponent },

  // Routes des séances (protégées)
  {
    path: 'seances',
    canActivate: [authGuard],
    children: [
      { path: '', component: SeanceListComponent },
      { path: 'planning', component: SeancePlanningComponent },
      {
        path: ':id',
        loadComponent: () => import('./features/seance/seance-detail/seance-detail.component').then(m => m.SeanceDetailComponent)
      }
    ]
  },
  {
    path: 'reservations',
    canActivate: [authGuard],
    component: ReservationListComponent
  },
  {
    path: 'bilan',
    canActivate: [authGuard, sportifGuard],
    component: BilanComponent
  },
  {
    path: 'profil',
    canActivate: [authGuard],
    component: ProfileComponent
  },


  // Route par défaut
  { path: '**', redirectTo: '' }
];
