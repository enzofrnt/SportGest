import { Component, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-footer',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './footer.component.html',
  styleUrl: './footer.component.scss'
})
export class FooterComponent implements OnInit {
  currentYear: number = 0;
  isAuthenticated = false;

  constructor(private authService: AuthService) {}

  ngOnInit(): void {
    this.currentYear = new Date().getFullYear();
    this.authService.currentUser$.subscribe(user => {
      this.isAuthenticated = !!user;
    });
  }

  logout(): void {
    this.authService.logout();
  }
}
