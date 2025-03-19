import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { CoachService } from '../../../services/coach.service';
import { Coach } from '../../../models/coach.model';

@Component({
  selector: 'app-coaches',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './coaches.component.html',
  styleUrl: './coaches.component.scss'
})
export class CoachesComponent implements OnInit {
  coaches: Coach[] = [];
  loading = true;
  error: string | null = null;

  constructor(private coachService: CoachService) {}

  ngOnInit(): void {
    this.loadCoaches();
  }

  async loadCoaches(): Promise<void> {
    try {
      const coaches$ = await this.coachService.getAllCoachs();
      coaches$.subscribe({
        next: (coaches) => {
          this.coaches = coaches;
          this.loading = false;
        },
        error: (err) => {
          this.error = 'Erreur lors du chargement des coachs. Veuillez réessayer plus tard.';
          this.loading = false;
          console.error('Erreur de chargement des coachs:', err);
        }
      });
    } catch (error) {
      this.error = 'Erreur lors du chargement des coachs. Veuillez réessayer plus tard.';
      this.loading = false;
      console.error('Erreur de chargement des coachs:', error);
    }
  }

  /**
   * Obtenir les initiales d'un coach à partir de son nom et prénom
   */
  getCoachInitials(coach: Coach): string {
    if (!coach.prenom || !coach.nom) return '?';
    return `${coach.prenom.charAt(0)}${coach.nom.charAt(0)}`.toUpperCase();
  }
}
