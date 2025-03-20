import { Component, OnInit } from '@angular/core';
import { Location } from '@angular/common';
import { Router, RouterModule, ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { Seance } from '../../../models/seance.model';
import { StatutSeance } from '../../../models/enum/statut-seance.enum';
import { SeanceApiService } from '../../../services/seance-api.service';
import { AuthService } from '../../../services/auth.service';
import { DifficulteExercice } from '../../../models/enum/difficulte-exercice.enum';
import { ReservationService } from '../../../services/reservation-api.service';
import { firstValueFrom } from 'rxjs';
import { Reservation } from '../../../models/reservation.model';

@Component({
  selector: 'app-seance-detail',
  templateUrl: './seance-detail.component.html',
  styleUrls: ['./seance-detail.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule]
})
export class SeanceDetailComponent implements OnInit {
  seance!: Seance;
  StatutSeance = StatutSeance;
  DifficulteExercice = DifficulteExercice;
  isAuthenticated = false;
  loading = true;
  error = '';
  isReserved = false;
  currentReservation: Reservation | null = null;

  constructor(
    private location: Location,
    private router: Router,
    private route: ActivatedRoute,
    private seanceApiService: SeanceApiService,
    private authService: AuthService,
    private reservationService: ReservationService
  ) {}

  ngOnInit() {
    this.isAuthenticated = this.authService.isAuthenticated();
    this.loadSeance();
  }

  private async loadSeance(): Promise<void> {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.error = 'ID de séance invalide';
      this.loading = false;
      return;
    }

    try {
      const seance = await this.seanceApiService.getSeanceById(id);
      this.seance = seance;

      if (this.isAuthenticated) {
        const checkReservation$ = await this.reservationService.checkReservation(id);
        const result = await firstValueFrom(checkReservation$);
        this.isReserved = result.isReserved;
        this.currentReservation = result.reservation || null;
      }
    } catch (error) {
      this.error = 'Erreur lors du chargement des détails de la séance';
    } finally {
      this.loading = false;
    }
  }

  goBack(): void {
    this.router.navigate(['/seances']);
  }

  async reserver(): Promise<void> {
    if (!this.seance?.id) {
      this.error = 'ID de séance invalide';
      return;
    }

    try {
      const response$ = await this.reservationService.createReservation(this.seance.id);
      const response = await firstValueFrom(response$);

      // Mettre à jour l'état local
      this.isReserved = true;
      this.currentReservation = response.reservation;

      // Rediriger vers la liste des réservations
      this.router.navigate(['/reservations']);
    } catch (error: any) {
      this.error = error.error?.error || 'Erreur lors de la réservation';
    }
  }

  async annulerReservation(): Promise<void> {
    if (!confirm('Voulez-vous vraiment annuler cette réservation ?')) {
      return;
    }

    if (!this.currentReservation?.id) {
      this.error = 'Impossible d\'annuler la réservation';
      return;
    }

    try {
      const response$ = await this.reservationService.cancelReservation(this.currentReservation.id);
      await firstValueFrom(response$);

      // Mettre à jour l'état local
      this.isReserved = false;
      this.currentReservation = null;

      // Recharger la séance pour mettre à jour la liste des participants
      await this.loadSeance();
    } catch (error: any) {
      this.error = error.error?.error || 'Erreur lors de l\'annulation de la réservation';
    }
  }

  voirReservations(): void {
    this.router.navigate(['/reservations']);
  }
}
