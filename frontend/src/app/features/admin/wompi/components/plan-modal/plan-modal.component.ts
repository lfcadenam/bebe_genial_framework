import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CreatePlanPayload, WompiPlan } from '../../../../../core/services/admin-commercial.service';
import { AdminUsersService } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-plan-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './plan-modal.component.html',
})
export class PlanModalComponent {
  private commercialService = inject(AdminCommercialService);
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() set plan(val: WompiPlan | null) {
    this._plan = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get plan(): WompiPlan | null {
    return this._plan;
  }

  @Output() close = new EventEmitter<void>();
  @Output() planSaved = new EventEmitter<void>();

  private _plan: WompiPlan | null = null;
  public courses = this.usersService.courses;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form: CreatePlanPayload = {
    name: '',
    type: 'recurring',
    price: 49900,
    currency: 'COP',
    duration_months: 1,
    trial_days: 0,
    is_active: true,
    benefits: '',
    related_courses: [],
  };

  private initForm(p: WompiPlan): void {
    this.form = {
      name: p.name || '',
      type: p.type || 'recurring',
      price: Number(p.price) || 0,
      currency: p.currency || 'COP',
      duration_months: p.duration_months || 1,
      trial_days: p.trial_days || 0,
      is_active: p.is_active,
      benefits: p.benefits || '',
      related_courses: p.related_courses ? [...p.related_courses] : [],
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    this.form = {
      name: '',
      type: 'recurring',
      price: 49900,
      currency: 'COP',
      duration_months: 1,
      trial_days: 0,
      is_active: true,
      benefits: '',
      related_courses: [],
    };
    this.errorMessage.set(null);
  }

  public toggleCourse(courseId: number): void {
    if (!this.form.related_courses) {
      this.form.related_courses = [];
    }
    const idx = this.form.related_courses.indexOf(courseId);
    if (idx > -1) {
      this.form.related_courses.splice(idx, 1);
    } else {
      this.form.related_courses.push(courseId);
    }
  }

  public isCourseSelected(courseId: number): boolean {
    return (this.form.related_courses || []).includes(courseId);
  }

  public onSubmit(): void {
    this.isSaving.set(true);
    this.errorMessage.set(null);

    const action$ = this.plan
      ? this.commercialService.updateWompiPlan(this.plan.id, this.form)
      : this.commercialService.createWompiPlan(this.form);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.planSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar el plan de pago.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
