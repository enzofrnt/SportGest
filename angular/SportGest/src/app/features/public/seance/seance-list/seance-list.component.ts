import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SeanceApiService } from '../../../../services/seance-api.service';
import { FormsModule } from '@angular/forms';
import { RouterModule, Router } from '@angular/router';
import { Seance } from '../../../../models/seance.model';
import { TypeSeance } from '../../../../models/enum/type-seance.enum';
import { NiveauSportif } from '../../../../models/enum/niveau-sportif.enum';
import { StatutSeance } from '../../../../models/enum/statut-seance.enum';
import { AuthService } from '../../../../services/auth.service';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { ApiService } from '../../../../services/api.service';
import { Coach } from '../../../../models/coach.model';
import { Exercice } from '../../../../models/exercice.model';
import { DifficulteExercice } from '../../../../models/enum/difficulte-exercice.enum';

@Component({
  selector: 'app-seance-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './seance-list.component.html',
  styleUrl: './seance-list.component.scss'
})
export class SeanceListComponent implements OnInit {
  // Rendre l'énumération accessible dans le template
  StatutSeance = StatutSeance;
  TypeSeance = TypeSeance;

  seances: Seance[] = [];
  filteredSeances: Seance[] = [];
  selectedSeance: Seance | null = null;
  loading = true;
  error = '';
  showFilters = false;
  isAuthenticated = false;
  publicCoachs: Coach[] = [];

  // Message d'information pour les utilisateurs non authentifiés

  // Terme de recherche
  searchTerm: string = '';

  // Filtres
  typeSeanceFilter: string = '';
  niveauSeanceFilter: string = '';
  dateMinFilter: string = '';
  dateMaxFilter: string = '';
  statutFilter: string = '';

  typesSeance = Object.values(TypeSeance);
  niveauxSportif = Object.values(NiveauSportif);
  statutsSeance = Object.values(StatutSeance);

  constructor(
    private seanceApiService: SeanceApiService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.checkAuth();
    this.loadSeances();
  }

  private checkAuth(): void {
    this.isAuthenticated = this.authService.isAuthenticated();
  }

  toggleFilters(): void {
    this.showFilters = !this.showFilters;
  }

  async loadSeances(): Promise<void> {
    this.loading = true;
    this.error = '';

    try {
      const seances$ = await this.seanceApiService.getAllSeance({
        type_seance: this.typeSeanceFilter || undefined,
        niveau_seance: this.niveauSeanceFilter || undefined,
        date_min: this.dateMinFilter || undefined,
        date_max: this.dateMaxFilter || undefined,
        statut: this.statutFilter || undefined
      });

      seances$.subscribe({
        next: (seances) => {
          console.log('Séances reçues de l\'API:', seances); // Debug
          this.seances = seances;
          this.filteredSeances = seances;
          // this.filterSeances();
          this.loading = false;
        },
        error: (error) => {
          console.error('Erreur API:', error); // Debug
          this.error = 'Erreur lors du chargement des séances';
          this.loading = false;
        }
      });
    } catch (error) {
      console.error('Erreur:', error); // Debug
      this.error = 'Erreur lors du chargement des séances';
      this.loading = false;
    }

    console.log('Séances chargées:', this.seances); // Debug
  }

  applyFilters(): void {
    this.loadSeances();
  }

  async viewSeanceDetails(id: number): Promise<void> {
    await this.router.navigate(['/seances', id]);
  }

  resetFilters(): void {
    this.searchTerm = '';
    this.typeSeanceFilter = '';
    this.niveauSeanceFilter = '';
    this.dateMinFilter = '';
    this.dateMaxFilter = '';
    this.statutFilter = '';
    this.loadSeances();
  }
}
