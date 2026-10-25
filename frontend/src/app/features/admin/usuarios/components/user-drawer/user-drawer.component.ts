import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminUser, AdminUsersService, UpdateUserPayload } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-user-drawer',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-drawer.component.html',
})
export class UserDrawerComponent {
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() set user(val: AdminUser | null) {
    this._user = val;
    if (val) {
      this.initForm(val);
    }
  }
  get user(): AdminUser | null {
    return this._user;
  }

  @Output() close = new EventEmitter<void>();
  @Output() userUpdated = new EventEmitter<void>();

  private _user: AdminUser | null = null;
  public isSaving = signal<boolean>(false);
  public isResettingPassword = signal<boolean>(false);
  public successMessage = signal<string | null>(null);
  public errorMessage = signal<string | null>(null);

  // Form Model
  public form = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    role: 'parent',
    status: 'active',
    must_change_password: false,
    baby_full_name: '',
    baby_birth_date: '',
    baby_school_grade: '',
    baby_school_name: '',
  };

  private initForm(u: AdminUser): void {
    this.form = {
      first_name: u.first_name || '',
      last_name: u.last_name || '',
      email: u.email || '',
      phone: u.phone || '',
      role: u.role || 'parent',
      status: u.status || 'active',
      must_change_password: u.must_change_password || false,
      baby_full_name: u.baby_profile?.full_name || '',
      baby_birth_date: u.baby_profile?.birth_date || '',
      baby_school_grade: u.baby_profile?.school_grade || '',
      baby_school_name: u.baby_profile?.school_name || '',
    };
    this.successMessage.set(null);
    this.errorMessage.set(null);
  }

  public onSubmit(): void {
    if (!this.user) return;
    this.isSaving.set(true);
    this.successMessage.set(null);
    this.errorMessage.set(null);

    const payload: UpdateUserPayload = { ...this.form };

    this.usersService.updateUser(this.user.id, payload).subscribe({
      next: (res) => {
        this.isSaving.set(false);
        this.successMessage.set('Información guardada exitosamente.');
        this.userUpdated.emit();
        setTimeout(() => this.successMessage.set(null), 3000);
      },
      error: (err) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al actualizar el usuario.');
      },
    });
  }

  public onResetPassword(): void {
    if (!this.user) return;
    if (!confirm(`¿Restablecer la contraseña del usuario ${this.user.full_name} a '123'?`)) {
      return;
    }

    this.isResettingPassword.set(true);
    this.successMessage.set(null);
    this.errorMessage.set(null);

    this.usersService.resetPassword(this.user.id, '123', true).subscribe({
      next: (res) => {
        this.isResettingPassword.set(false);
        this.successMessage.set("Contraseña restablecida exitosamente a '123'.");
        this.userUpdated.emit();
        setTimeout(() => this.successMessage.set(null), 4000);
      },
      error: (err) => {
        this.isResettingPassword.set(false);
        this.errorMessage.set('No fue posible restablecer la contraseña.');
      },
    });
  }

  public onDeleteEnrollment(enrollmentId: number, courseTitle: string): void {
    if (!confirm(`¿Estás seguro de cancelar el acceso al curso "${courseTitle}"?`)) {
      return;
    }

    this.usersService.deleteEnrollment(enrollmentId).subscribe({
      next: () => {
        this.successMessage.set(`Matrícula para "${courseTitle}" cancelada.`);
        if (this.user) {
          this.usersService.getUserById(this.user.id).subscribe((res) => {
            this.user = res.data;
          });
        }
        this.userUpdated.emit();
        setTimeout(() => this.successMessage.set(null), 3000);
      },
      error: () => {
        this.errorMessage.set('Error al eliminar la matrícula.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
