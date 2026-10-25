import { Component, EventEmitter, Input, OnInit, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService } from '../../../../../core/services/admin-academic.service';
import { AdminUsersService, AdminCourse } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-trial-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './trial-modal.component.html',
})
export class TrialModalComponent implements OnInit {
  private academicService = inject(AdminAcademicService);
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Output() close = new EventEmitter<void>();
  @Output() trialAssigned = new EventEmitter<void>();

  public courses = signal<AdminCourse[]>([]);
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    user_id: null as number | null,
    course_id: null as number | null,
    days: 7,
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
      this.errorMessage.set('Debes ingresar el ID de usuario y seleccionar un curso.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    this.academicService.assignTrial({
      user_id: Number(this.form.user_id),
      course_id: Number(this.form.course_id),
      days: Number(this.form.days) || 7,
    }).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.trialAssigned.emit();
        this.close.emit();
      },
      error: (err) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al asignar el acceso demo.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
