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

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  async onSubmit() {
    if (!this.email || !this.password) return;

    this.isLoading = true;
    this.error = '';

    try {
      const login$ = await this.authService.login(this.email, this.password);
      login$.subscribe({
        next: () => {
          this.router.navigate(['/membre']);
        },
        error: (err) => {
          this.error = err.error.message || 'Une erreur est survenue';
          this.isLoading = false;
        }
      });
    } catch (err) {
      this.error = 'Une erreur est survenue';
      this.isLoading = false;
    }
  }
}
