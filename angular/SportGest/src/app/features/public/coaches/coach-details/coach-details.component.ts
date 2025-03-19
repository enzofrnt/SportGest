import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { CoachService } from '../../../../services/coach.service';
import { Coach } from '../../../../models/coach.model';
import { Specialite } from '../../../../models/specialite.model';
import { Seance } from '../../../../models/seance.model';
import { StatutSeance } from '../../../../models/enum/statut-seance.enum';

@Component({
  selector: 'app-coach-details',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './coach-details.component.html',
  styleUrl: './coach-details.component.scss'
})
export class CoachDetailsComponent implements OnInit {
  coachId!: number;
  coach: Coach | null = null;
  specialites: Specialite[] = [];
  seances: Seance[] = [];
  loading = true;
  error: string | null = null;
  statutSeance = StatutSeance;

  constructor(
    private route: ActivatedRoute,
    private coachService: CoachService
  ) {}

  ngOnInit(): void {
    console.log("ngOnInit");  
    this.route.params.subscribe(params => {
      if (params['id']) {
        this.coachId = +params['id'];
        this.loadCoachDetails();
      }
    });
  }

  async loadCoachDetails(): Promise<void> {
    try {
      this.loading = true;
      
      // Charger les détails du coach
      const coach$ = await this.coachService.getCoachById(this.coachId);
      coach$.subscribe({
        next: (coach) => {
          this.coach = coach;
          this.loadSpecialities();
          this.loadSessions();
        },
        error: (err) => {
          this.error = 'Erreur lors du chargement des détails du coach.';
          this.loading = false;
          console.error('Erreur de chargement des détails du coach:', err);
        }
      });
    } catch (error) {
      this.error = 'Erreur lors du chargement des détails du coach.';
      this.loading = false;
      console.error('Erreur de chargement des détails du coach:', error);
    }
  }

  async loadSpecialities(): Promise<void> {
    try {
      const specialites$ = await this.coachService.getCoachSpecialities(this.coachId);
      specialites$.subscribe({
        next: (specialites) => {
          this.specialites = specialites;
          if (this.coach && !this.coach.specialites) {
            this.coach.specialites = specialites;
          }
          this.checkLoading();
        },
        error: (err) => {
          console.error('Erreur de chargement des spécialités:', err);
          this.checkLoading();
        }
      });
    } catch (error) {
      console.error('Erreur de chargement des spécialités:', error);
      this.checkLoading();
    }
  }

  async loadSessions(): Promise<void> {
    try {
      const seances$ = await this.coachService.getCoachSessions(this.coachId);
      seances$.subscribe({
        next: (seances) => {
          this.seances = seances;
          this.checkLoading();
        },
        error: (err) => {
          console.error('Erreur de chargement des séances:', err);
          this.checkLoading();
        }
      });
    } catch (error) {
      console.error('Erreur de chargement des séances:', error);
      this.checkLoading();
    }
  }

  getNombreSportifs(seance: Seance): string {
    return seance.sportifs ? `${seance.sportifs.length}/10` : '0/10';
  }

  private checkLoading(): void {
    // On considère que le chargement est terminé une fois que les détails du coach
    // et soit les spécialités, soit les séances sont chargées
    if (this.coach && (this.specialites.length > 0 || this.seances.length > 0)) {
      this.loading = false;
    }
  }

  /**
   * Obtenir les initiales d'un coach à partir de son nom et prénom
   */
  getCoachInitials(): string {
    if (!this.coach || !this.coach.prenom || !this.coach.nom) return '?';
    return `${this.coach.prenom.charAt(0)}${this.coach.nom.charAt(0)}`.toUpperCase();
  }
} 