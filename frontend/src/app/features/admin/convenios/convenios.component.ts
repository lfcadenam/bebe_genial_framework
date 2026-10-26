import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CommercialPartner } from '../../../core/services/admin-commercial.service';
import { AdminUsersService } from '../../../core/services/admin-users.service';
import { PartnerDrawerComponent } from './components/partner-drawer/partner-drawer.component';
import { PartnerStudentsModalComponent } from './components/partner-students-modal/partner-students-modal.component';
import { PartnerQrModalComponent } from './components/partner-qr-modal/partner-qr-modal.component';
import { PartnerPasswordModalComponent } from './components/partner-password-modal/partner-password-modal.component';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';

@Component({
  selector: 'app-convenios',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    PartnerDrawerComponent,
    PartnerStudentsModalComponent,
    PartnerQrModalComponent,
    PartnerPasswordModalComponent,
    ConfirmModalComponent,
  ],
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
  public isQrModalOpen = signal<boolean>(false);
  public isPasswordModalOpen = signal<boolean>(false);
  public isExportingAll = signal<boolean>(false);
  public selectedPartner = signal<CommercialPartner | null>(null);

  // Diálogo de Confirmación Nuvex Modern Curve UI
  public confirmDialog = signal<{
    isOpen: boolean;
    title: string;
    message: string;
    confirmText: string;
    cancelText: string;
    type: 'danger' | 'warning' | 'info';
    action: () => void;
  }>({
    isOpen: false,
    title: '',
    message: '',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    type: 'danger',
    action: () => {},
  });

  // Notificación Flotante / Toast Nuvex UI
  public toast = signal<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);

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

  public openQrModal(partner: CommercialPartner): void {
    this.selectedPartner.set(partner);
    this.isQrModalOpen.set(true);
  }

  public closeQrModal(): void {
    this.isQrModalOpen.set(false);
  }

  public openPasswordModal(partner: CommercialPartner): void {
    this.selectedPartner.set(partner);
    this.isPasswordModalOpen.set(true);
  }

  public closePasswordModal(): void {
    this.isPasswordModalOpen.set(false);
  }

  public onToggleStatus(partner: CommercialPartner): void {
    const isActivating = partner.status !== 'active';
    const actionText = isActivating ? 'activar' : 'inactivar';

    this.confirmDialog.set({
      isOpen: true,
      title: isActivating ? 'Activar Aliado Comercial' : 'Inactivar Aliado Comercial',
      message: `¿Deseas ${actionText} a "${partner.company_name}"? ${
        isActivating
          ? 'El distribuidor y sus accesos asociados volverán a estar operativos en la plataforma.'
          : 'El distribuidor no podrá matricular nuevos alumnos mientras su estado sea inactivo.'
      }`,
      confirmText: isActivating ? 'Sí, Activar' : 'Sí, Inactivar',
      cancelText: 'Cancelar',
      type: isActivating ? 'info' : 'warning',
      action: () => {
        this.commercialService.togglePartnerStatus(partner.id).subscribe({
          next: (res) => {
            this.loadPartners();
            this.showFlash(res.message);
            this.closeConfirmDialog();
          },
          error: () => {
            this.showFlash('Error al cambiar el estado del aliado.', 'error');
            this.closeConfirmDialog();
          },
        });
      },
    });
  }

  public onPasswordReset(newPass: string): void {
    this.showFlash('Contraseña actualizada con éxito en la cuenta del distribuidor.');
  }

  public onExportPartnerStudents(partner: CommercialPartner): void {
    this.commercialService.exportPartnerStudents(partner.id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const sanitized = partner.company_name.toLowerCase().replace(/[^a-z0-9]/g, '_');
        a.download = `estudiantes_${sanitized}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        this.showFlash(`Reporte de estudiantes de "${partner.company_name}" descargado con éxito.`);
      },
      error: () => this.showFlash('Error al exportar estudiantes del aliado.', 'error'),
    });
  }

  public onExportAllStudents(): void {
    this.isExportingAll.set(true);
    this.commercialService.exportAllStudents().subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `todos_los_estudiantes_distribuidores_${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        this.isExportingAll.set(false);
        this.showFlash('Reporte general de todos los estudiantes descargado con éxito.');
      },
      error: () => {
        this.isExportingAll.set(false);
        this.showFlash('Error al exportar reporte general.', 'error');
      },
    });
  }

  public onPartnerSaved(): void {
    this.loadPartners();
    this.showFlash('Aliado comercial guardado correctamente.');
  }

  public onDeletePartner(partner: CommercialPartner): void {
    this.confirmDialog.set({
      isOpen: true,
      title: 'Eliminar Aliado Comercial',
      message: `¿Estás seguro de eliminar el aliado "${partner.company_name}"? Esta acción desactivará sus accesos comerciales y los enlaces asociados.`,
      confirmText: 'Sí, Eliminar',
      cancelText: 'Cancelar',
      type: 'danger',
      action: () => {
        this.commercialService.deletePartner(partner.id).subscribe({
          next: () => {
            this.loadPartners();
            this.showFlash(`Aliado "${partner.company_name}" eliminado correctamente.`);
            this.closeConfirmDialog();
          },
          error: () => {
            this.showFlash('Error al eliminar el aliado.', 'error');
            this.closeConfirmDialog();
          },
        });
      },
    });
  }

  public closeConfirmDialog(): void {
    this.confirmDialog.update((state) => ({ ...state, isOpen: false }));
  }

  public onConfirmDialogAction(): void {
    const current = this.confirmDialog();
    if (current.action) {
      current.action();
    }
  }

  public showFlash(message: string, type: 'success' | 'error' | 'info' = 'success'): void {
    this.toast.set({ message, type });
    setTimeout(() => this.toast.set(null), 4000);
  }
}
