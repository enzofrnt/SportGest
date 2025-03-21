import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './register/register.component';

@NgModule({
  imports: [
    CommonModule,
    RouterModule.forChild([
      { path: 'connexion', component: LoginComponent },
      { path: 'inscription', component: RegisterComponent }
    ])
  ]
})
export class AuthModule { }
