import { Component, OnInit, OnDestroy, ViewChild, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';
import { Utilisateur } from '../../../models/utilisateur.model';
import { Subscription } from 'rxjs';
import { ClickOutsideDirective } from '../../directives/click-outside.directive';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [CommonModule, RouterModule, ClickOutsideDirective],
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit, OnDestroy {
  @ViewChild('userMenuButton') userMenuButton!: ElementRef;

  isAuthenticated: boolean = false;
  isUserMenuOpen: boolean = false;
  isMobileMenuOpen: boolean = false;
  user: Utilisateur | null = null;
  isSportif: boolean = false;
  private userSubscription: Subscription | null = null;

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    // S'abonner aux changements d'état d'authentification
    this.userSubscription = this.authService.currentUser$.subscribe((user: Utilisateur | null) => {
      this.isAuthenticated = !!user;
      this.user = user;
      this.isSportif = this.authService.isSportif();
    });
    console.log(this.user);
  }

  ngOnDestroy(): void {
    // Nettoyer la souscription lors de la destruction du composant
    if (this.userSubscription) {
      this.userSubscription.unsubscribe();
    }
  }

  formatRole(role: string): string {
    if (role === 'ROLE_USER') return 'Utilisateur';
    if (role === 'ROLE_ADMIN') return 'Administrateur';
    if (role === 'ROLE_COACH') return 'Coach';
    return role.replace('ROLE_', '');
  }

  getPrimaryRole(): string {
    if (!this.user || !this.user.roles || this.user.roles.length === 0) {
      return 'Sportif';
    }

    // Priorité des rôles
    if (this.user.roles.includes('ROLE_ADMIN')) {
      return 'Administrateur';
    } else if (this.user.roles.includes('ROLE_COACH')) {
      return 'Coach';
    } else if (this.user.roles.includes('ROLE_USER')) {
      return 'Sportif';
    }

    // Si aucun rôle reconnu, prendre le premier
    return this.formatRole(this.user.roles[0]);
  }

  toggleUserMenu(): void {
    this.isUserMenuOpen = !this.isUserMenuOpen;
    if (this.isUserMenuOpen) {
      this.isMobileMenuOpen = false;
    }
  }

  closeUserMenu(): void {
    this.isUserMenuOpen = false;
  }

  toggleMobileMenu(): void {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
    if (this.isMobileMenuOpen) {
      this.isUserMenuOpen = false;
    }
  }

  closeMobileMenu(): void {
    this.isMobileMenuOpen = false;
  }

  goToDashboard(): void {
    this.router.navigate(['/membre']);
    this.isUserMenuOpen = false;
  }

  logout(): void {
    this.authService.logout();
    this.isUserMenuOpen = false;
    this.isMobileMenuOpen = false;
    this.router.navigate(['/']);
  }
}
