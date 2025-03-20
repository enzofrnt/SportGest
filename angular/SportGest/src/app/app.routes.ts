import { Routes } from '@angular/router';
import { LoginComponent } from './auth/login/login.component';
import { RegisterComponent } from './auth/register/register.component';
import { HomeComponent } from './features/public/home/home.component';
import { CoachListComponent } from './features/public/coach/coache-list/coach-list.component';
import { SeanceListComponent } from './features/public/seance/seance-list/seance-list.component';
import { SeancePlanningComponent } from './features/public/seance/seance-planning/seance-planning.component';
import { authGuard } from './guards/auth.guard';
import { ReservationListComponent } from './features/public/reservation/reservation-list/reservation-list.component';
export const routes: Routes = [
  // Route principale - redirige vers la racine
  { path: '', component: HomeComponent },

  // Routes publiques
  { path: 'coachs', component: CoachListComponent },
  {
    path: 'coachs/:id',
    loadComponent: () => import('./features/public/coach/coach-details/coach-details.component').then(m => m.CoachDetailsComponent),
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
        loadComponent: () => import('./features/public/seance/seance-detail/seance-detail.component').then(m => m.SeanceDetailComponent)
      }
    ]
  },
  {
    path: 'reservations',
    canActivate: [authGuard],
    component: ReservationListComponent
  },

  // Route par défaut
  { path: '**', redirectTo: '' }
];
