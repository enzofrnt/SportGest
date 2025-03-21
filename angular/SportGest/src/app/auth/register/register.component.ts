import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.scss'],
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule]
})
export class RegisterComponent {
  registerForm: FormGroup;
  errorMessage: string = '';
  isLoading: boolean = false;

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.registerForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      nom: ['', [Validators.required]],
      prenom: ['', [Validators.required]]
    });
  }

  async onSubmit() {
    if (this.registerForm.valid) {
      this.isLoading = true;
      this.errorMessage = '';

      const { email, password, nom, prenom } = this.registerForm.value;

      try {
        const registerObservable = await this.authService.register(email, password, nom, prenom);
        registerObservable.subscribe({
          next: () => {
            this.router.navigate(['/']);
          },
          error: (error: any) => {
            this.errorMessage = error.error.message || 'Une erreur est survenue lors de l\'inscription';
            this.isLoading = false;
          }
        });
      } catch (error: any) {
        this.errorMessage = 'Une erreur est survenue lors de l\'inscription';
        this.isLoading = false;
      }
    }
  }
}
