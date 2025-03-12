import { Routes } from '@angular/router';
import { LoginComponent } from './auth/login/login.component';
import { RegisterComponent } from './auth/register/register.component';
import { HomeComponent } from './features/public/home/home.component';
import { CoachesComponent } from './features/public/coaches/coaches.component';
import { SessionsComponent } from './features/public/sessions/sessions.component';
import { PlanningComponent as PublicPlanningComponent } from './features/public/planning/planning.component';
import { DashboardComponent } from './features/member/dashboard/dashboard.component';
import { PlanningComponent as MemberPlanningComponent } from './features/member/planning/planning.component';
import { SessionDetailsComponent } from './features/member/session-details/session-details.component';
import { BookingComponent } from './features/member/booking/booking.component';
import { HistoryComponent } from './features/member/history/history.component';
import { StatsComponent } from './features/member/stats/stats.component';
import { ProfileComponent } from './features/member/profile/profile.component';
import { ReservationsComponent } from './features/member/reservations/reservations.component';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  // Route principale - redirige vers la racine
  { path: '', component: HomeComponent },
  // Routes publiques
  { path: 'coachs', component: CoachesComponent },
  { path: 'seances', component: SessionsComponent },
  { path: 'planning', component: PublicPlanningComponent },
  { path: 'inscription', component: RegisterComponent },
  { path: 'connexion', component: LoginComponent },
  
  // Routes protégées (sportifs connectés)
  { 
    path: 'membre', 
    canActivate: [authGuard],
    children: [
      { path: '', component: DashboardComponent },
      { path: 'planning', component: MemberPlanningComponent },
      { path: 'seance/:id', component: SessionDetailsComponent },
      { path: 'reservation', component: BookingComponent },
      { path: 'historique', component: HistoryComponent },
      { path: 'statistiques', component: StatsComponent },
      { path: 'profil', component: ProfileComponent },
      { path: 'reservations', component: ReservationsComponent },
    ]
  },
  
  // Route par défaut
  { path: '**', redirectTo: '' }
];
