import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, WompiPlan } from '../../../core/services/admin-commercial.service';
import { AdminUsersService } from '../../../core/services/admin-users.service';
import { PlanModalComponent } from './components/plan-modal/plan-modal.component';

@Component({
  selector: 'app-wompi',
  standalone: true,
  imports: [CommonModule, FormsModule, PlanModalComponent],
  templateUrl: './wompi.component.html',
})
export class WompiComponent implements OnInit {
  public commercialService = inject(AdminCommercialService);
  public usersService = inject(AdminUsersService);

  // Pestaña activa ('planes' | 'transacciones')
  public activeTab = signal<'planes' | 'transacciones'>('planes');

  // Filtros de transacciones
  public transactionSearch = signal<string>('');
  public statusFilter = signal<string>('');
  public currentPage = signal<number>(1);

  // Modal Plan
  public isPlanModalOpen = signal<boolean>(false);
  public selectedPlan = signal<WompiPlan | null>(null);

  // Flash toast
  public flashMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.loadMetrics();
    this.loadPlans();
    this.usersService.getCourses().subscribe();
  }

  public setTab(tab: 'planes' | 'transacciones'): void {
    this.activeTab.set(tab);
    if (tab === 'transacciones' && !this.commercialService.wompiSubscriptions().length) {
      this.loadSubscriptions();
    }
  }

  public loadMetrics(): void {
    this.commercialService.getWompiMetrics().subscribe();
  }

  public loadPlans(): void {
    this.commercialService.getWompiPlans().subscribe();
  }

  public loadSubscriptions(): void {
    this.commercialService
      .getWompiSubscriptions({
        search: this.transactionSearch(),
        status: this.statusFilter() || undefined,
        page: this.currentPage(),
        per_page: 15,
      })
      .subscribe();
  }

  public onSearchChange(term: string): void {
    this.transactionSearch.set(term);
    this.currentPage.set(1);
    this.loadSubscriptions();
  }

  public setStatusFilter(status: string): void {
    this.statusFilter.set(status);
    this.currentPage.set(1);
    this.loadSubscriptions();
  }

  public onPageChange(page: number): void {
    if (page >= 1 && page <= this.commercialService.wompiSubscriptionsMeta().last_page) {
      this.currentPage.set(page);
      this.loadSubscriptions();
    }
  }

  public openNewPlan(): void {
    this.selectedPlan.set(null);
    this.isPlanModalOpen.set(true);
  }

  public openEditPlan(plan: WompiPlan): void {
    this.selectedPlan.set(plan);
    this.isPlanModalOpen.set(true);
  }

  public closePlanModal(): void {
    this.isPlanModalOpen.set(false);
  }

  public onPlanSaved(): void {
    this.loadPlans();
    this.loadMetrics();
    this.showFlash('Plan de suscripción guardado correctamente.');
  }

  public onTogglePlan(plan: WompiPlan): void {
    this.commercialService.toggleWompiPlanStatus(plan.id).subscribe({
      next: (res) => {
        this.loadPlans();
        this.loadMetrics();
        this.showFlash(`Estado del plan "${plan.name}" actualizado.`);
      },
      error: () => this.showFlash('Error al actualizar estado del plan.'),
    });
  }

  public getCourseName(courseId: number): string {
    const course = this.usersService.courses().find((c) => c.id === courseId);
    return course ? course.title : `Curso #${courseId}`;
  }

  private showFlash(msg: string): void {
    this.flashMessage.set(msg);
    setTimeout(() => this.flashMessage.set(null), 4000);
  }
}
