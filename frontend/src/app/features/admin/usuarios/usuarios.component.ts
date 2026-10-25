import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminUser, AdminUsersService } from '../../../core/services/admin-users.service';
import { UserDrawerComponent } from './components/user-drawer/user-drawer.component';
import { MatriculaModalComponent } from './components/matricula-modal/matricula-modal.component';

@Component({
  selector: 'app-usuarios',
  standalone: true,
  imports: [CommonModule, FormsModule, UserDrawerComponent, MatriculaModalComponent],
  templateUrl: './usuarios.component.html',
})
export class UsuariosComponent implements OnInit {
  public usersService = inject(AdminUsersService);

  // Filtros reactivos
  public searchTerm = signal<string>('');
  public roleFilter = signal<string>('');
  public statusFilter = signal<string>('');
  public currentPage = signal<number>(1);

  // Estados modales y drawer
  public isDrawerOpen = signal<boolean>(false);
  public isModalOpen = signal<boolean>(false);
  public activeUser = signal<AdminUser | null>(null);

  // Mensaje flash
  public flashMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.loadUsers();
    this.usersService.getCourses().subscribe();
  }

  public loadUsers(): void {
    this.usersService
      .getUsers({
        search: this.searchTerm(),
        role: this.roleFilter() || undefined,
        status: this.statusFilter() || undefined,
        page: this.currentPage(),
        per_page: 15,
      })
      .subscribe();
  }

  public onSearchChange(term: string): void {
    this.searchTerm.set(term);
    this.currentPage.set(1);
    this.loadUsers();
  }

  public setRoleFilter(role: string): void {
    this.roleFilter.set(role);
    this.currentPage.set(1);
    this.loadUsers();
  }

  public setStatusFilter(status: string): void {
    this.statusFilter.set(status);
    this.currentPage.set(1);
    this.loadUsers();
  }

  public onPageChange(page: number): void {
    if (page >= 1 && page <= this.usersService.meta().last_page) {
      this.currentPage.set(page);
      this.loadUsers();
    }
  }

  public openDrawer(user: AdminUser): void {
    this.usersService.getUserById(user.id).subscribe({
      next: (res) => {
        this.activeUser.set(res.data);
        this.isDrawerOpen.set(true);
      },
      error: () => {
        this.activeUser.set(user);
        this.isDrawerOpen.set(true);
      },
    });
  }

  public closeDrawer(): void {
    this.isDrawerOpen.set(false);
  }

  public openMatriculaModal(user: AdminUser): void {
    this.activeUser.set(user);
    this.isModalOpen.set(true);
  }

  public closeMatriculaModal(): void {
    this.isModalOpen.set(false);
  }

  public onUserUpdated(): void {
    this.loadUsers();
    this.showFlash('Registro actualizado correctamente.');
  }

  public onEnrollmentCreated(): void {
    this.loadUsers();
    this.showFlash('Matrícula asignada con éxito al alumno.');
  }

  public onQuickResetPassword(user: AdminUser): void {
    if (!confirm(`¿Restablecer la contraseña de ${user.full_name} a '123'?`)) {
      return;
    }

    this.usersService.resetPassword(user.id, '123', true).subscribe({
      next: () => {
        this.showFlash(`Contraseña de ${user.full_name} restablecida a '123'.`);
      },
      error: () => {
        this.showFlash('Error al restablecer la contraseña.');
      },
    });
  }

  public exportToCsv(): void {
    const list = this.usersService.users();
    if (!list.length) {
      alert('No hay datos para exportar.');
      return;
    }

    const headers = ['ID', 'Nombre Completo', 'Email', 'Telefono', 'Rol', 'Estado', 'Bebe', 'Grado', 'Matriculas Activas'];
    const rows = list.map((u) => [
      u.id,
      `"${u.full_name}"`,
      `"${u.email}"`,
      `"${u.phone || ''}"`,
      u.role,
      u.status,
      `"${u.baby_profile?.full_name || ''}"`,
      `"${u.baby_profile?.school_grade || ''}"`,
      u.active_enrollments,
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e) => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `alumnos_bebegenial_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  private showFlash(msg: string): void {
    this.flashMessage.set(msg);
    setTimeout(() => this.flashMessage.set(null), 4000);
  }
}
