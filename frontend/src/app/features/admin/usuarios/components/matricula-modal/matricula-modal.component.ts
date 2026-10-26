import { Component, EventEmitter, Input, OnInit, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCourse, AdminUser, AdminUsersService, CreateEnrollmentPayload } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-matricula-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './matricula-modal.component.html',
})
export class MatriculaModalComponent implements OnInit {
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() user: AdminUser | null = null;
  @Input() set courses(val: AdminCourse[]) {
    this._courses = val || [];
    if (this._courses.length > 0 && (!this.form.course_id || !this._courses.some(c => c.id === Number(this.form.course_id)))) {
      this.form.course_id = this._courses[0].id;
    }
    this.updateAccessTypeForCourse();
  }
  get courses(): AdminCourse[] {
    return this._courses;
  }
  private _courses: AdminCourse[] = [];

  @Output() close = new EventEmitter<void>();
  @Output() enrollmentCreated = new EventEmitter<void>();

  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    course_id: 1,
    access_type: 'FULL' as 'FULL' | 'FREEMIUM',
    book_limit: 5,
    duration_days: 365,
    expires_at: '',
  };

  ngOnInit(): void {
    this.initDefaultExpiration();
  }

  public initDefaultExpiration(): void {
    if (!this.form.duration_days) {
      this.form.duration_days = 365;
    }
    this.onDurationDaysChange(this.form.duration_days);
  }

  /**
   * Determina si el curso seleccionado corresponde a Cuentos Leo con LEO o cursos con libros/cuentos
   */
  public isCourseWithBooks(): boolean {
    const courseId = Number(this.form.course_id);
    const course = this.courses.find((c) => Number(c.id) === courseId);
    if (!course) return false;
    const text = `${course.title || ''} ${course.description || ''}`.toLowerCase();
    return (
      text.includes('leo con leo') ||
      text.includes('cuento') ||
      text.includes('libro') ||
      courseId === 24
    );
  }

  public onCourseChange(courseId: number | string): void {
    this.form.course_id = Number(courseId);
    this.updateAccessTypeForCourse();
  }

  public updateAccessTypeForCourse(): void {
    if (!this.isCourseWithBooks()) {
      this.form.access_type = 'FULL';
    }
  }

  public onDurationDaysChange(days: number | string): void {
    const num = Number(days);
    this.form.duration_days = num;
    if (num > 0) {
      const d = new Date();
      d.setDate(d.getDate() + num);
      this.form.expires_at = d.toISOString().split('T')[0];
    }
  }

  public onExpiresAtChange(dateStr: string): void {
    this.form.expires_at = dateStr;
    if (dateStr) {
      const target = new Date(dateStr + 'T00:00:00');
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const diffTime = target.getTime() - today.getTime();
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      if (diffDays > 0) {
        this.form.duration_days = diffDays;
      }
    }
  }

  public setDurationPreset(days: number): void {
    this.onDurationDaysChange(days);
  }

  public getVigenciaAproxText(days: number): string {
    if (!days || days <= 0) return '';
    if (days === 30) return '1 mes';
    if (days === 60) return '2 meses';
    if (days === 90) return '3 meses';
    if (days === 180) return '6 meses';
    if (days === 365) return '1 año';
    if (days === 730) return '2 años';
    if (days < 30) return `${days} días`;
    const months = Math.round(days / 30);
    if (months < 12) return `${months} meses`;
    const years = (days / 365).toFixed(1).replace('.0', '');
    return `${years} año${Number(years) > 1 ? 's' : ''}`;
  }

  public onSubmit(): void {
    if (!this.user) return;
    this.isSaving.set(true);
    this.errorMessage.set(null);

    const isBookCourse = this.isCourseWithBooks();
    const accessType = isBookCourse ? this.form.access_type : 'FULL';

    const payload: CreateEnrollmentPayload = {
      course_id: Number(this.form.course_id),
      access_type: accessType,
      book_limit: isBookCourse && accessType === 'FREEMIUM' ? Number(this.form.book_limit) : undefined,
    };

    if (this.form.expires_at) {
      payload.expires_at = this.form.expires_at;
    } else {
      payload.duration_days = Number(this.form.duration_days || 365);
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
