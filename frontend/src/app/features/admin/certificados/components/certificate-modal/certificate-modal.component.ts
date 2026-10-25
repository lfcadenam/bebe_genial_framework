import { Component, EventEmitter, Input, OnInit, Output, inject, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { AdminAcademicService } from '../../../../../core/services/admin-academic.service';
import { AdminUsersService, AdminCourse } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-certificate-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './certificate-modal.component.html',
})
export class CertificateModalComponent implements OnInit {
  private academicService = inject(AdminAcademicService);
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Output() close = new EventEmitter<void>();
  @Output() certificateEmitted = new EventEmitter<void>();

  public courses = signal<AdminCourse[]>([]);
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    user_id: null as number | null,
    course_id: null as number | null,
    grade: 'Aprobado con Excelencia',
    certificate_code: '',
  };

  ngOnInit(): void {
    this.usersService.getCourses().subscribe({
      next: (res) => {
        const list = res.data || [];
        this.courses.set(list);
        if (list.length > 0) {
          this.form.course_id = list[0].id;
        }
      },
    });
  }

  public onSubmit(): void {
    if (!this.form.user_id || !this.form.course_id) {
      this.errorMessage.set('Debes ingresar el ID de usuario y seleccionar un curso pedagógico.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    const payload: any = {
      user_id: Number(this.form.user_id),
      course_id: Number(this.form.course_id),
      grade: this.form.grade,
    };
    if (this.form.certificate_code.trim()) {
      payload.certificate_code = this.form.certificate_code.trim();
    }

    this.academicService.emitCertificate(payload).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.certificateEmitted.emit();
        this.close.emit();
      },
      error: (err) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al emitir el diploma.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
