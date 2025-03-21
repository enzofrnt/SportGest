import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  email: string = '';
  password: string = '';
  isLoading: boolean = false;
  error: string = '';
  errorType: string = '';

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  async onSubmit() {
    if (!this.email || !this.password) {
      this.error = 'Veuillez remplir tous les champs';
      this.errorType = 'missing_fields';
      return;
    }

    this.isLoading = true;
    this.error = '';
    this.errorType = '';

    try {
      const login$ = await this.authService.login(this.email, this.password);
      login$.subscribe({
        next: () => {
          if (this.authService.isSportif()) {
            this.router.navigate(['/bilan']);
          } else {
            this.authService.logout();
            this.error = 'Seul les sportifs peuvent se connecter';
            this.errorType = 'unauthorized_role';
          }
          this.isLoading = false;
        },
        error: (err) => {
          console.log('Erreur de connexion:', err);
          this.isLoading = false;

          if (err.error && err.error.message) {
            this.error = err.error.message;
            this.errorType = err.error.error || 'unknown';
          } else if (err.status === 0) {
            this.error = 'Impossible de se connecter au serveur. Veuillez vérifier votre connexion.';
            this.errorType = 'server_error';
          } else if (err.status === 401) {
            if (err.error && err.error.error === 'user_not_found') {
              this.error = 'Aucun compte n\'existe avec cet email';
              this.errorType = 'user_not_found';
            } else if (err.error && err.error.error === 'invalid_password') {
              this.error = 'Mot de passe incorrect';
              this.errorType = 'invalid_password';
            } else {
              this.error = 'Email ou mot de passe incorrect';
              this.errorType = 'invalid_credentials';
            }
          } else {
            this.error = 'Une erreur est survenue lors de la connexion';
            this.errorType = 'unknown';
          }
        }
      });
    } catch (err) {
      this.isLoading = false;
      this.error = 'Une erreur est survenue lors de la connexion';
      this.errorType = 'unknown';
    }
  }
}
