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
import { FullCalendarModule } from '@fullcalendar/angular';
import { CalendarOptions, EventClickArg } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

@Component({
  selector: 'app-seance-planning',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule, FullCalendarModule],
  templateUrl: './seance-planning.component.html',
  styleUrl: './seance-planning.component.scss'
})
export class SeancePlanningComponent implements OnInit {
  // Rendre l'énumération accessible dans le template
  StatutSeance = StatutSeance;
  TypeSeance = TypeSeance;

  seances: Seance[] = [];
  loading = true;
  error = '';
  isAuthenticated = false;

  // Configuration du calendrier
  calendarOptions: CalendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    locale: 'fr',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    slotMinTime: '07:00:00',
    slotMaxTime: '22:00:00',
    allDaySlot: false,
    slotDuration: '01:00:00',
    height: 'auto',
    eventClick: this.handleEventClick.bind(this),
    events: []
  };

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

  async loadSeances(): Promise<void> {
    this.loading = true;
    this.error = '';

    try {
      const seances$ = await this.seanceApiService.getAllSeance();
      seances$.subscribe({
        next: (seances) => {
          this.seances = seances;
          this.updateCalendarEvents();
          this.loading = false;
        },
        error: (error) => {
          this.error = 'Erreur lors du chargement des séances';
          this.loading = false;
        }
      });
    } catch (error) {
      this.error = 'Erreur lors du chargement des séances';
      this.loading = false;
    }
  }

  private updateCalendarEvents(): void {
    const events = this.seances.map(seance => ({
      id: seance.id?.toString(),
      title: seance.theme?.nom || 'Séance sans thème',
      start: new Date(seance.dateHeure),
      end: new Date(new Date(seance.dateHeure).getTime() + seance.dureeSeance * 60 * 1000), // Conversion des minutes en millisecondes
      backgroundColor: this.getEventColor(seance.statut),
      borderColor: this.getEventColor(seance.statut),
      extendedProps: {
        seance: seance
      }
    }));

    this.calendarOptions.events = events;
  }

  private getEventColor(statut: StatutSeance): string {
    switch (statut) {
      case StatutSeance.PREVUE:
        return '#f1c40f'; // Jaune
      case StatutSeance.VALIDEE:
        return '#2ecc71'; // Vert
      case StatutSeance.ANNULEE:
        return '#95a5a6'; // Gris
      default:
        return '#3498db'; // Bleu par défaut
    }
  }

  handleEventClick(arg: EventClickArg) {
    const seance = arg.event.extendedProps['seance'] as Seance;
    if (seance.id) {
      this.router.navigate(['/seances', seance.id], { state: { previousUrl: '/seances/planning' } });
    }
  }
}
