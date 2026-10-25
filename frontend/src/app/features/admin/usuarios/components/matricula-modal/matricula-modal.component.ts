import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCourse, AdminUser, AdminUsersService, CreateEnrollmentPayload } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-matricula-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './matricula-modal.component.html',
})
export class MatriculaModalComponent {
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() user: AdminUser | null = null;
  @Input() courses: AdminCourse[] = [];

  @Output() close = new EventEmitter<void>();
  @Output() enrollmentCreated = new EventEmitter<void>();

  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    course_id: 1,
    access_type: 'FULL' as 'FULL' | 'FREEMIUM',
    book_limit: 5,
    duration_type: '365', // 30, 90, 180, 365, custom
    custom_expires_at: '',
  };

  public onSubmit(): void {
    if (!this.user) return;
    this.isSaving.set(true);
    this.errorMessage.set(null);

    const payload: CreateEnrollmentPayload = {
      course_id: Number(this.form.course_id),
      access_type: this.form.access_type,
      book_limit: this.form.access_type === 'FREEMIUM' ? Number(this.form.book_limit) : undefined,
    };

    if (this.form.duration_type === 'custom' && this.form.custom_expires_at) {
      payload.expires_at = this.form.custom_expires_at;
    } else {
      payload.duration_days = Number(this.form.duration_type);
    }

    this.usersService.storeEnrollment(this.user.id, payload).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.enrollmentCreated.emit();
        this.close.emit();
      },
      error: (err) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al asignar la matrícula.');
      },
    });
  }

  public onClose(): void {
    this.errorMessage.set(null);
    this.close.emit();
  }
}
