import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CommercialPartner, CreatePartnerPayload } from '../../../../../core/services/admin-commercial.service';
import { AdminCourse, AdminUsersService } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-partner-drawer',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './partner-drawer.component.html',
})
export class PartnerDrawerComponent {
  private commercialService = inject(AdminCommercialService);
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() set partner(val: CommercialPartner | null) {
    this._partner = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get partner(): CommercialPartner | null {
    return this._partner;
  }

  @Output() close = new EventEmitter<void>();
  @Output() partnerSaved = new EventEmitter<void>();

  private _partner: CommercialPartner | null = null;
  public courses = this.usersService.courses;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form: CreatePartnerPayload = {
    company_name: '',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    is_agreement: false,
    status: 'active',
    allowed_courses: [],
    allowed_months: 12,
    notes: '',
  };

  private initForm(p: CommercialPartner): void {
    this.form = {
      company_name: p.company_name || '',
      contact_name: p.contact_name || '',
      contact_email: p.contact_email || '',
      contact_phone: p.contact_phone || '',
      is_agreement: p.is_agreement,
      status: p.status || 'active',
      allowed_courses: p.allowed_courses ? [...p.allowed_courses] : [],
      allowed_months: p.allowed_months || 12,
      notes: p.notes || '',
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    this.form = {
      company_name: '',
      contact_name: '',
      contact_email: '',
      contact_phone: '',
      is_agreement: false,
      status: 'active',
      allowed_courses: [],
      allowed_months: 12,
      notes: '',
    };
    this.errorMessage.set(null);
  }

  public toggleCourse(courseId: number): void {
    if (!this.form.allowed_courses) {
      this.form.allowed_courses = [];
    }
    const idx = this.form.allowed_courses.indexOf(courseId);
    if (idx > -1) {
      this.form.allowed_courses.splice(idx, 1);
    } else {
      this.form.allowed_courses.push(courseId);
    }
  }

  public isCourseSelected(courseId: number): boolean {
    return (this.form.allowed_courses || []).includes(courseId);
  }

  public onSubmit(): void {
    this.isSaving.set(true);
    this.errorMessage.set(null);

    const action$ = this.partner
      ? this.commercialService.updatePartner(this.partner.id, this.form)
      : this.commercialService.createPartner(this.form);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.partnerSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar los datos del aliado.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
