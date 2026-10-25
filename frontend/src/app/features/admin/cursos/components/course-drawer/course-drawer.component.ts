import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, CourseCatalogItem } from '../../../../../core/services/admin-academic.service';

@Component({
  selector: 'app-course-drawer',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './course-drawer.component.html',
})
export class CourseDrawerComponent {
  private academicService = inject(AdminAcademicService);

  @Input() isOpen = false;
  @Input() set course(val: CourseCatalogItem | null) {
    this._course = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get course(): CourseCatalogItem | null {
    return this._course;
  }

  @Output() close = new EventEmitter<void>();
  @Output() courseSaved = new EventEmitter<void>();

  private _course: CourseCatalogItem | null = null;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    title: '',
    description: '',
    hours: 40,
    price: 0,
    is_visible: true,
    is_active: true,
    cover_image: '',
    background_image: '',
  };

  private initForm(c: CourseCatalogItem): void {
    this.form = {
      title: c.title,
      description: c.description || '',
      hours: c.hours || 0,
      price: Number(c.price) || 0,
      is_visible: c.is_visible,
      is_active: c.status === 'active' || (c as any).is_active !== false,
      cover_image: c.cover_image || '',
      background_image: c.background_image || '',
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    this.form = {
      title: '',
      description: '',
      hours: 40,
      price: 0,
      is_visible: true,
      is_active: true,
      cover_image: '',
      background_image: '',
    };
    this.errorMessage.set(null);
  }

  public onSubmit(): void {
    if (!this.form.title.trim()) {
      this.errorMessage.set('El título del curso es obligatorio.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    const payload = {
      ...this.form,
      hours: Number(this.form.hours),
      price: Number(this.form.price),
    };

    const action$ = this.course
      ? this.academicService.updateCourse(this.course.id, payload)
      : this.academicService.createCourse(payload);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.courseSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar el curso.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
