import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators, AbstractControl, ValidationErrors } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../services/auth.service';
import { Utilisateur } from '../../../models/utilisateur.model';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.scss'
})
export class ProfileComponent implements OnInit {
  user: Utilisateur | null = null;
  loading = true;
  error = '';
  profileForm!: FormGroup;
  passwordForm!: FormGroup;
  notificationsForm!: FormGroup;
  totalReservations = 0;
  completedSessions = 0;

  constructor(
    private authService: AuthService,
    private fb: FormBuilder,
    private router: Router
  ) { }

  ngOnInit(): void {
    // D'abord initialiser les formulaires
    this.initForms();
    // Ensuite charger les données
    this.loadUserData();
  }

  loadUserData(): void {
    this.loading = true;
    this.user = this.authService.currentUserValue;
    
    if (!this.user) {
      this.error = 'Impossible de charger les données utilisateur';
      this.loading = false;
      return;
    }

    // Ici, vous pourriez récupérer des données supplémentaires pour l'utilisateur
    // comme les statistiques, réservations, etc. via d'autres services
    
    // Simulation des données de statistiques
    this.totalReservations = 5;
    this.completedSessions = 3;
    
    // Mettre à jour les formulaires APRÈS avoir obtenu les données
    this.updateFormValues();
    this.loading = false;
  }

  initForms(): void {
    // Formulaire des informations du profil
    this.profileForm = this.fb.group({
      prenom: ['', Validators.required],
      nom: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]]
    });

    // Formulaire de changement de mot de passe
    this.passwordForm = this.fb.group({
      currentPassword: ['', Validators.required],
      newPassword: ['', [Validators.required, Validators.minLength(8)]],
      confirmPassword: ['', Validators.required]
    }, { validators: this.passwordMatchValidator });

    // Formulaire des préférences de notification
    this.notificationsForm = this.fb.group({
      emailNotifications: [true],
      reminderNotifications: [true],
      promotionalNotifications: [false]
    });
  }

  updateFormValues(): void {
    if (this.user && this.profileForm) {
      this.profileForm.patchValue({
        prenom: this.user.prenom,
        nom: this.user.nom,
        email: this.user.email
      });
    }
  }

  passwordMatchValidator(control: AbstractControl): ValidationErrors | null {
    const newPassword = control.get('newPassword')?.value;
    const confirmPassword = control.get('confirmPassword')?.value;

    if (newPassword !== confirmPassword) {
      return { 'passwordMismatch': true };
    }

    return null;
  }

  getUserInitials(): string {
    if (!this.user) return '';
    return (this.user.prenom.charAt(0) + this.user.nom.charAt(0)).toUpperCase();
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

  onSubmit(): void {
    if (this.profileForm.invalid) return;

    this.loading = true;
    const updatedUserData = {
      prenom: this.profileForm.value.prenom,
      nom: this.profileForm.value.nom,
      email: this.profileForm.value.email
    };

    // Appel au service pour mettre à jour les informations utilisateur
    this.authService.updateUserProfile(updatedUserData)
      .then(updatedUser => {
        this.user = updatedUser;
        this.loading = false;
        // Afficher un message de succès
        console.log('Profil mis à jour avec succès');
      })
      .catch(error => {
        this.loading = false;
        this.error = 'Erreur lors de la mise à jour du profil. Veuillez réessayer.';
        console.error('Erreur de mise à jour:', error);
      });
  }

  onChangePassword(): void {
    if (this.passwordForm.invalid) return;

    this.loading = true;
    const passwordData = {
      currentPassword: this.passwordForm.value.currentPassword,
      newPassword: this.passwordForm.value.newPassword
    };

    // Ici, appelez votre service pour changer le mot de passe
    // this.userService.changePassword(passwordData).subscribe(...)
    
    // Simulation de mise à jour
    setTimeout(() => {
      this.loading = false;
      this.passwordForm.reset();
      // Afficher un message de succès (à implémenter)
      console.log('Mot de passe changé avec succès');
    }, 800);
  }

  onUpdateNotifications(): void {
    if (this.notificationsForm.invalid) return;

    this.loading = true;
    const notificationPreferences = this.notificationsForm.value;

    // Ici, appelez votre service pour mettre à jour les préférences de notification
    // this.userService.updateNotificationPreferences(notificationPreferences).subscribe(...)
    
    // Simulation de mise à jour
    setTimeout(() => {
      this.loading = false;
      // Afficher un message de succès (à implémenter)
      console.log('Préférences de notification mises à jour avec succès');
    }, 800);
  }
}
