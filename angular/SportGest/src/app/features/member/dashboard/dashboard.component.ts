import { Component, OnInit, AfterViewInit, ViewChild, ElementRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { BilanService } from '../../../services/bilan.service';
import { BilanSportif } from '../../../models/bilan.model';
import { AuthService } from '../../../services/auth.service';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss'
})
export class DashboardComponent implements OnInit, AfterViewInit {
  @ViewChild('typesSeancesChart') typesSeancesChart!: ElementRef;
  
  private bilanService = inject(BilanService);
  private authService = inject(AuthService);
  
  bilan: BilanSportif | null = null;
  loading = true;
  error = '';
  
  // Modal de période
  showPeriodeModal = false;
  
  // Options pour le filtre de période
  periodeOptions = [
    { value: 'week', label: 'Cette semaine' },
    { value: 'month', label: 'Ce mois' },
    { value: 'trimester', label: 'Ce trimestre' },
    { value: 'year', label: 'Cette année' },
    { value: 'all', label: 'Tout' }
  ];
  selectedPeriode = 'month';
  
  // Pour la période personnalisée
  customDateMin: string = '';
  customDateMax: string = '';
  
  constructor() {
    // Initialiser les dates pour la période personnalisée
    const today = new Date();
    const lastMonth = new Date();
    lastMonth.setMonth(today.getMonth() - 1);
    
    this.customDateMin = lastMonth.toISOString().split('T')[0];
    this.customDateMax = today.toISOString().split('T')[0];
  }

  ngOnInit() {
    this.loadBilan();
  }

  ngAfterViewInit() {
    if (this.bilan) {
      this.renderCharts();
    }
  }
  
  // Afficher/masquer le modal de sélection de période
  togglePeriodeModal() {
    this.showPeriodeModal = !this.showPeriodeModal;
  }
  
  // Sélectionner une période
  selectPeriode(periodeValue: string) {
    this.selectedPeriode = periodeValue;
  }
  
  // Appliquer la période sélectionnée
  applyPeriode() {
    this.loadBilan();
    this.togglePeriodeModal();
  }
  
  // Obtenir le libellé de la période sélectionnée
  getPeriodeLabel(): string {
    if (this.selectedPeriode === 'custom') {
      const dateMin = new Date(this.customDateMin);
      const dateMax = new Date(this.customDateMax);
      return `${dateMin.toLocaleDateString()} - ${dateMax.toLocaleDateString()}`;
    }
    
    const option = this.periodeOptions.find(opt => opt.value === this.selectedPeriode);
    return option ? option.label : 'Cette semaine';
  }

  loadBilan() {
    this.loading = true;
    
    // Dates pour la période sélectionnée
    let dateMin: string | undefined;
    let dateMax: string | undefined;
    
    const today = new Date();
    
    switch (this.selectedPeriode) {
      case 'week':
        const lastWeek = new Date(today);
        lastWeek.setDate(today.getDate() - 7);
        dateMin = lastWeek.toISOString().split('T')[0];
        break;
      case 'month':
        const lastMonth = new Date(today);
        lastMonth.setMonth(today.getMonth() - 1);
        dateMin = lastMonth.toISOString().split('T')[0];
        break;
      case 'trimester':
        const lastTrimester = new Date(today);
        lastTrimester.setMonth(today.getMonth() - 3);
        dateMin = lastTrimester.toISOString().split('T')[0];
        break;
      case 'year':
        const lastYear = new Date(today);
        lastYear.setFullYear(today.getFullYear() - 1);
        dateMin = lastYear.toISOString().split('T')[0];
        break;
      case 'custom':
        // Utiliser les dates personnalisées
        dateMin = this.customDateMin;
        dateMax = this.customDateMax;
        break;
      case 'all':
        // Aucune date min, récupère tout l'historique
        break;
    }
    
    // Si dateMax n'est pas déjà défini (cas personnalisé), utiliser aujourd'hui
    if (!dateMax) {
      dateMax = today.toISOString().split('T')[0];
    }
    
    // Récupérer l'ID du sportif connecté
    const currentUser = this.authService.currentUserValue;
    
    if (currentUser && currentUser.id) {
      this.bilanService.getBilanSportif(currentUser.id, dateMin, dateMax)
        .subscribe({
          next: (data: BilanSportif) => {
            this.bilan = data;
            this.loading = false;
            
            // Attendre que la vue soit prête pour rendre les graphiques
            setTimeout(() => {
              this.renderCharts();
            }, 0);
          },
          error: (err) => {
            console.error('Erreur lors du chargement du bilan', err);
            
            // Si erreur d'authentification, ne pas afficher d'erreur car l'AuthService
            // redirigera automatiquement vers la page de connexion
            if (err.status === 401) {
              this.loading = false;
              return;
            }
            
            this.error = 'Erreur lors du chargement du bilan. Veuillez réessayer.';
            this.loading = false;
          }
        });
    } else {
      this.error = 'Utilisateur non authentifié';
      this.loading = false;
    }
  }

  renderCharts() {
    if (!this.bilan || !this.typesSeancesChart) return;
    
    // Graphique des types de séances
    const typesSeancesCtx = this.typesSeancesChart.nativeElement.getContext('2d');
    
    if (typesSeancesCtx) {
      // Détruire le graphique existant s'il y en a un
      Chart.getChart(this.typesSeancesChart.nativeElement)?.destroy();
      
      // Données pour le graphique
      const labels = this.bilan.typesSeances.map(type => type.type);
      const data = this.bilan.typesSeances.map(type => type.nbSeances);
      
      // Créer le graphique
      new Chart(typesSeancesCtx, {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: data,
            backgroundColor: [
              '#4e73df',
              '#1cc88a',
              '#36b9cc',
              '#f6c23e',
              '#e74a3b',
              '#6f42c1',
              '#fd7e14'
            ],
            hoverBackgroundColor: [
              '#2e59d9',
              '#17a673',
              '#2c9faf',
              '#dda20a',
              '#be2617',
              '#5a368a',
              '#e06000'
            ],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: {
            padding: 20
          },
          plugins: {
            legend: {
              position: 'right',
              labels: {
                font: {
                  size: 14
                },
                padding: 20
              }
            },
            tooltip: {
              bodyFont: {
                size: 14
              },
              padding: 15
            }
          },
          cutout: '0%',
          radius: '90%'
        }
      });
    }
  }

  // Convertir les minutes en format heures/minutes
  formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h${mins ? mins + 'min' : ''}`;
  }
}
