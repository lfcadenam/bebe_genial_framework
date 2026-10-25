import { Injectable, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap, finalize } from 'rxjs';
import { environment } from '../../../environments/environment';
import { ApiResponse } from '../models/auth.models';
import { AdminDashboardData } from '../models/admin-dashboard.models';

@Injectable({
  providedIn: 'root',
})
export class AdminDashboardService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  readonly metrics = signal<AdminDashboardData | null>(null);
  readonly isLoading = signal<boolean>(false);
  readonly error = signal<string | null>(null);

  getDashboardMetrics(): Observable<ApiResponse<AdminDashboardData>> {
    this.isLoading.set(true);
    this.error.set(null);

    return this.http
      .get<ApiResponse<AdminDashboardData>>(`${this.apiUrl}/admin/dashboard/metrics`)
      .pipe(
        tap({
          next: (response) => {
            if (response.success && response.data) {
              this.metrics.set(response.data);
            }
          },
          error: (err) => {
            const msg = err.error?.message || 'Error al cargar las métricas administrativas.';
            this.error.set(msg);
          },
        }),
        finalize(() => this.isLoading.set(false))
      );
  }
}
