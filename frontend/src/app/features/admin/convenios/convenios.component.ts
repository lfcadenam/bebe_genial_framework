import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CommercialPartner } from '../../../core/services/admin-commercial.service';
import { AdminUsersService } from '../../../core/services/admin-users.service';
import { PartnerDrawerComponent } from './components/partner-drawer/partner-drawer.component';
import { PartnerStudentsModalComponent } from './components/partner-students-modal/partner-students-modal.component';

@Component({
  selector: 'app-convenios',
  standalone: true,
  imports: [CommonModule, FormsModule, PartnerDrawerComponent, PartnerStudentsModalComponent],
  templateUrl: './convenios.component.html',
})
export class ConveniosComponent implements OnInit {
  public commercialService = inject(AdminCommercialService);
  public usersService = inject(AdminUsersService);

  // Filtros reactivos
  public searchTerm = signal<string>('');
  public typeFilter = signal<string>(''); // '', 'agreement', 'distributor'
  public statusFilter = signal<string>('');
  public currentPage = signal<number>(1);

  // Modales y Drawer
  public isDrawerOpen = signal<boolean>(false);
  public isStudentsModalOpen = signal<boolean>(false);
  public selectedPartner = signal<CommercialPartner | null>(null);

  // Toast
  public flashMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.loadPartners();
    this.usersService.getCourses().subscribe();
  }

  public loadPartners(): void {
    this.commercialService
      .getPartners({
        search: this.searchTerm(),
        type: this.typeFilter() || undefined,
        status: this.statusFilter() || undefined,
        page: this.currentPage(),
        per_page: 15,
      })
      .subscribe();
  }

  public onSearchChange(term: string): void {
    this.searchTerm.set(term);
    this.currentPage.set(1);
    this.loadPartners();
  }

  public setTypeFilter(type: string): void {
    this.typeFilter.set(type);
    this.currentPage.set(1);
    this.loadPartners();
  }

  public setStatusFilter(status: string): void {
    this.statusFilter.set(status);
    this.currentPage.set(1);
    this.loadPartners();
  }

  public onPageChange(page: number): void {
    if (page >= 1 && page <= this.commercialService.partnersMeta().last_page) {
      this.currentPage.set(page);
      this.loadPartners();
    }
  }

  public openNewPartner(): void {
    this.selectedPartner.set(null);
    this.isDrawerOpen.set(true);
  }

  public openEditPartner(partner: CommercialPartner): void {
    this.selectedPartner.set(partner);
    this.isDrawerOpen.set(true);
  }

  public closeDrawer(): void {
    this.isDrawerOpen.set(false);
  }

  public openStudentsModal(partner: CommercialPartner): void {
    this.selectedPartner.set(partner);
    this.isStudentsModalOpen.set(true);
  }

  public closeStudentsModal(): void {
    this.isStudentsModalOpen.set(false);
  }

  public onPartnerSaved(): void {
    this.loadPartners();
    this.showFlash('Aliado comercial guardado correctamente.');
  }

  public onDeletePartner(partner: CommercialPartner): void {
    if (!confirm(`¿Estás seguro de eliminar el aliado "${partner.company_name}"?`)) {
      return;
    }

    this.commercialService.deletePartner(partner.id).subscribe({
      next: () => {
        this.loadPartners();
        this.showFlash(`Aliado "${partner.company_name}" eliminado correctamente.`);
      },
      error: () => {
        this.showFlash('Error al eliminar el aliado.');
      },
    });
  }

  private showFlash(msg: string): void {
    this.flashMessage.set(msg);
    setTimeout(() => this.flashMessage.set(null), 4000);
  }
}
