import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SeanceApiService } from '../../../services/seance-api.service';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { Seance } from '../../../models/seance.model';
import { TypeSeance } from '../../../models/enum/type-seance.enum';
import { NiveauSportif } from '../../../models/enum/niveau-sportif.enum';
import { StatutSeance } from '../../../models/enum/statut-seance.enum';
import { AuthService } from '../../../services/auth.service';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { ApiService } from '../../../services/api.service';
import { Coach } from '../../../models/coach.model';
import { Exercice } from '../../../models/exercice.model';
import { DifficulteExercice } from '../../../models/enum/difficulte-exercice.enum';

@Component({
  selector: 'app-sessions',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './sessions.component.html',
  styleUrl: './sessions.component.scss'
})
export class SessionsComponent implements OnInit {
  // Rendre l'énumération accessible dans le template
  StatutSeance = StatutSeance;
  TypeSeance = TypeSeance;
  
  seances: Seance[] = [];
  filteredSeances: Seance[] = [];
  selectedSeance: Seance | null = null;
  loading = false;
  error = '';
  showFilters = false;
  isAuthenticated = false;
  publicCoachs: Coach[] = [];
  
  // Message d'information pour les utilisateurs non authentifiés
  infoMessage = '';

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
    private http: HttpClient,
    private apiService: ApiService
  ) {}

  ngOnInit(): void {
    this.isAuthenticated = this.authService.isAuthenticated();
    
    // Charger les coachs pour tous les utilisateurs (publics)
    this.loadPublicCoachs();
    
    // Charger les séances
    this.loadSeances();
  }

  toggleFilters(): void {
    this.showFilters = !this.showFilters;
  }

  async loadPublicCoachs(): Promise<void> {
    try {
      const url = await this.apiService.getEndpointUrl('coachs');
      this.http.get<Coach[]>(url).subscribe({
        next: (coachs) => {
          this.publicCoachs = coachs;
        },
        error: (err) => {
          console.error('Erreur lors du chargement des coachs', err);
        }
      });
    } catch (err) {
      console.error('Erreur lors de la récupération de l\'URL des coachs', err);
    }
  }

  async loadSeances(): Promise<void> {
    this.loading = true;
    this.error = '';
    
    try {
      if (this.isAuthenticated) {
        // Si l'utilisateur est authentifié, charger les vraies séances
        this.seances = await this.seanceApiService.getSeances();
        this.infoMessage = '';
      } else {
        // Si non authentifié, créer des séances simulées basées sur les coachs réels
        this.infoMessage = 'Connectez-vous pour voir toutes les séances disponibles et réserver votre place.';
        this.seances = this.generateMockSeancesWithRealCoachs();
      }
      this.applyClientSideFilters();
    } catch (error) {
      console.error(error);
      if (error instanceof HttpErrorResponse && error.status === 401) {
        this.error = '';
        this.infoMessage = 'Connectez-vous pour voir toutes les séances disponibles et réserver votre place.';
        this.seances = this.generateMockSeancesWithRealCoachs();
        this.applyClientSideFilters();
      } else {
        this.error = 'Erreur lors du chargement des séances';
      }
    } finally {
      this.loading = false;
    }
  }

  // Méthode pour générer des données fictives basées sur de vrais coachs
  private generateMockSeancesWithRealCoachs(): Seance[] {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const nextWeek = new Date(today);
    nextWeek.setDate(today.getDate() + 7);
    
    // Types de séances réelles
    const typesSeanceValues = Object.values(TypeSeance);
    
    // Si pas de coachs réels, utiliser des coachs fictifs
    if (this.publicCoachs.length === 0) {
      return this.getMockSeances();
    }
    
    // Générer des séances fictives avec des vrais coachs
    return [
      {
        id: 1,
        themeSeance: 'Découverte Cardio',
        dateHeure: tomorrow,
        typeSeance: typesSeanceValues[0],
        statut: StatutSeance.PREVUE,
        niveauSeance: NiveauSportif.DEBUTANT,
        coach: this.publicCoachs[0],
        sportifs: [],
        exercices: [],
        theme: []
      },
      {
        id: 2,
        themeSeance: 'Renforcement musculaire',
        dateHeure: nextWeek,
        typeSeance: typesSeanceValues[1] || typesSeanceValues[0],
        statut: StatutSeance.VALIDEE,
        niveauSeance: NiveauSportif.INTERMEDIAIRE,
        coach: this.publicCoachs.length > 1 ? this.publicCoachs[1] : this.publicCoachs[0],
        sportifs: [],
        exercices: [],
        theme: []
      }
    ];
  }

  // Méthode de secours pour générer des données entièrement fictives
  private getMockSeances(): Seance[] {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const mockCoach1: Coach = { 
      id: 1, 
      nom: 'Dupont', 
      prenom: 'Jean',
      tarifHoraire: 50,
      specialites: [],
      email: 'jean.dupont@sportgest.fr'
    };
    
    const mockCoach2: Coach = { 
      id: 2, 
      nom: 'Martin', 
      prenom: 'Sophie',
      tarifHoraire: 45,
      specialites: [],
      email: 'sophie.martin@sportgest.fr'
    };
    
    return [
      {
        id: 1,
        themeSeance: 'Cardio et renforcement',
        dateHeure: today,
        typeSeance: Object.values(TypeSeance)[0],
        statut: StatutSeance.PREVUE,
        niveauSeance: NiveauSportif.DEBUTANT,
        coach: mockCoach1,
        sportifs: [],
        exercices: [],
        theme: []
      },
      {
        id: 2,
        themeSeance: 'Musculation complète',
        dateHeure: tomorrow,
        typeSeance: Object.values(TypeSeance)[1] || Object.values(TypeSeance)[0],
        statut: StatutSeance.VALIDEE,
        niveauSeance: NiveauSportif.INTERMEDIAIRE,
        coach: mockCoach2,
        sportifs: [],
        exercices: [],
        theme: []
      }
    ];
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
    
    try {
      if (this.isAuthenticated) {
        this.selectedSeance = await this.seanceApiService.getSeance(id);
      } else {
        // Pour les données fictives
        this.selectedSeance = this.seances.find(s => s.id === id) || null;
        
        // Ajouter des exercices fictifs pour la démonstration
        if (this.selectedSeance) {
          const mockExercices: Exercice[] = [
            { id: 1, nom: 'Pompes', description: 'Exercice pour les pectoraux', dureeEstimee: 10, difficulte: DifficulteExercice.MOYEN },
            { id: 2, nom: 'Squats', description: 'Exercice pour les jambes', dureeEstimee: 15, difficulte: DifficulteExercice.FACILE }
          ];
          this.selectedSeance.exercices = mockExercices;
        }
      }
    } catch (error) {
      console.error(error);
      if (error instanceof HttpErrorResponse && error.status === 401) {
        this.error = '';
        this.infoMessage = 'Vous devez être connecté pour voir les détails complets de cette séance';
      } else {
        this.error = 'Erreur lors du chargement des détails de la séance';
      }
    } finally {
      this.loading = false;
    }
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
