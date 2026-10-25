import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { AdminDashboardService } from '../../../core/services/admin-dashboard.service';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './dashboard.component.html',
})
export class AdminDashboardComponent implements OnInit {
  readonly dashboardService = inject(AdminDashboardService);
  readonly authService = inject(AuthService);

  ngOnInit(): void {
    this.dashboardService.getDashboardMetrics().subscribe();
  }

  reload(): void {
    this.dashboardService.getDashboardMetrics().subscribe();
  }
}
