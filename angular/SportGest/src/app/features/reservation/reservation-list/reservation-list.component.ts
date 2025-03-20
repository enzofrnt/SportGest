import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { ReservationService } from '../../../services/reservation-api.service';
import { Reservation } from '../../../models/reservation.model';
import { AuthService } from '../../../services/auth.service';
import { firstValueFrom } from 'rxjs';
import { StatutSeance } from '../../../models/enum/statut-seance.enum';

@Component({
  selector: 'app-reservation-list',
  templateUrl: './reservation-list.component.html',
  styleUrls: ['./reservation-list.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule]
})
export class ReservationListComponent implements OnInit {
  reservations: Reservation[] = [];
  loading = true;
  error: string | null = null;
  StatutSeance = StatutSeance; // Pour utiliser l'enum dans le template

  constructor(
    private reservationService: ReservationService,
    private authService: AuthService
  ) {}

  async ngOnInit(): Promise<void> {
    await this.loadReservations();
  }

  private async loadReservations(): Promise<void> {
    try {
      const currentUser = await firstValueFrom(this.authService.currentUser$);
      if (!currentUser?.id) {
        this.error = 'Utilisateur non connecté';
        return;
      }

      const reservations$ = await this.reservationService.getSportifReservations(currentUser.id);
      this.reservations = await firstValueFrom(reservations$);
    } catch (err) {
      this.error = 'Erreur lors du chargement des réservations';
      console.error('Erreur:', err);
    } finally {
      this.loading = false;
    }
  }

  async cancelReservation(id: number | undefined): Promise<void> {
    if (!id) {
      this.error = 'ID de réservation invalide';
      return;
    }

    try {
      const response$ = await this.reservationService.cancelReservation(id);
      await firstValueFrom(response$);
      await this.loadReservations(); // Recharger la liste après l'annulation
    } catch (err) {
      this.error = 'Erreur lors de l\'annulation de la réservation';
      console.error('Erreur:', err);
    }
  }
}
