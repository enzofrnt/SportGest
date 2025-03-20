import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SeanceApiService } from '../../../../services/seance-api.service';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
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
  ) {}

  ngOnInit(): void {
    // Charger les séances
    this.loadSeances();
  }

  toggleFilters(): void {
    this.showFilters = !this.showFilters;
  }

  async loadSeances(): Promise<void> {
    const seances$ = await this.seanceApiService.getAllSeance();
    seances$.subscribe({
      next: (seances) => {
        this.seances = seances;
        this.loading = false;
      },
      error: (error) => {
        this.error = error.message;
        this.loading = false;
      }
    });
  }

  applyFilters(): void {
    this.applyClientSideFilters();
  }

  applyClientSideFilters(): void {
    this.filteredSeances = this.seances.filter(seance => {
      // Filtre par terme de recherche (thème, coach, type)
      const searchMatch = !this.searchTerm ||
        seance.themeSeance.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        seance.coach.nom.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        seance.coach.prenom.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
        seance.typeSeance.toLowerCase().includes(this.searchTerm.toLowerCase());

      // Filtre par type de séance
      const typeMatch = !this.typeSeanceFilter || seance.typeSeance === this.typeSeanceFilter;

      // Filtre par niveau
      const niveauMatch = !this.niveauSeanceFilter || seance.niveauSeance === this.niveauSeanceFilter;

      // Filtre par statut
      const statutMatch = !this.statutFilter || seance.statut === this.statutFilter;

      // Filtre par date minimum
      const dateMin = this.dateMinFilter ? new Date(this.dateMinFilter) : null;
      const dateMinMatch = !dateMin || new Date(seance.dateHeure) >= dateMin;

      // Filtre par date maximum
      const dateMax = this.dateMaxFilter ? new Date(this.dateMaxFilter) : null;
      const dateMaxMatch = !dateMax || new Date(seance.dateHeure) <= dateMax;

      return searchMatch && typeMatch && niveauMatch && statutMatch && dateMinMatch && dateMaxMatch;
    });
  }

  async viewSeanceDetails(id: number): Promise<void> {
    this.loading = true;
    this.error = '';

    // try {
    //   if (this.isAuthenticated) {
    //     this.selectedSeance = await this.seanceApiService.getSeance(id);
    //   } else {
    //     this.infoMessage = 'Connectez-vous pour voir les détails de cette séance';
    //   }
    // } catch (error) {
    //   console.error(error);
    //   if (error instanceof HttpErrorResponse && error.status === 401) {
    //     this.error = '';
    //     this.infoMessage = 'Vous devez être connecté pour voir les détails complets de cette séance';
    //   } else {
    //     this.error = 'Erreur lors du chargement des détails de la séance';
    //   }
    // } finally {
    //   this.loading = false;
    // }
  }

  closeDetails(): void {
    this.selectedSeance = null;
  }

  resetFilters(): void {
    this.searchTerm = '';
    this.typeSeanceFilter = '';
    this.niveauSeanceFilter = '';
    this.dateMinFilter = '';
    this.dateMaxFilter = '';
    this.statutFilter = '';
    this.applyClientSideFilters();
  }
}
