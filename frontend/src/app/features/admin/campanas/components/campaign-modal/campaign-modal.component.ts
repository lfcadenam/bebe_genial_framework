import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { AdminApiCampanasService, ApiPartner, Campaign, CreateCampaignPayload } from '../../../../../core/services/admin-api-campanas.service';
import { AdminCourse, AdminUsersService } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-campaign-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './campaign-modal.component.html',
})
export class CampaignModalComponent {
  private campanasService = inject(AdminApiCampanasService);
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() partners: ApiPartner[] = [];
  @Input() set campaign(val: Campaign | null) {
    this._campaign = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get campaign(): Campaign | null {
    return this._campaign;
  }

  @Output() close = new EventEmitter<void>();
  @Output() campaignSaved = new EventEmitter<void>();

  private _campaign: Campaign | null = null;
  public courses = this.usersService.courses;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form: CreateCampaignPayload = {
    code: '',
    name: '',
    partner_id: null,
    course_id: 1,
    book_limit: 12,
    duration_days: 30,
    starts_at: new Date().toISOString().substring(0, 10),
    ends_at: new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().substring(0, 10),
    is_active: true,
  };

  private initForm(campaign: Campaign): void {
    this.form = {
      code: campaign.code,
      name: campaign.name,
      partner_id: campaign.partner_id || null,
      course_id: campaign.course_id,
      book_limit: campaign.book_limit,
      duration_days: campaign.duration_days,
      starts_at: campaign.starts_at?.substring(0, 10) || '',
      ends_at: campaign.ends_at?.substring(0, 10) || '',
      is_active: campaign.is_active,
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    const defaultCourse = this.courses().find((c: AdminCourse) => c.id === 24) || this.courses()[0];
    const today = new Date().toISOString().substring(0, 10);
    const endOfYear = `${new Date().getFullYear()}-12-31`;

    this.form = {
      code: '',
      name: '',
      partner_id: this.partners.length > 0 ? this.partners[0].id : null,
      course_id: defaultCourse ? defaultCourse.id : 1,
      book_limit: 12,
      duration_days: 30,
      starts_at: today,
      ends_at: endOfYear,
      is_active: true,
    };
    this.errorMessage.set(null);
  }

  public onSubmit(): void {
    if (!this.form.code.trim() || !this.form.name.trim()) {
      this.errorMessage.set('El código y el nombre de la campaña son obligatorios.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    const payload: CreateCampaignPayload = {
      ...this.form,
      code: this.form.code.toUpperCase().trim(),
      partner_id: this.form.partner_id ? Number(this.form.partner_id) : null,
      course_id: Number(this.form.course_id),
      book_limit: Number(this.form.book_limit),
      duration_days: Number(this.form.duration_days),
    };

    const action$ = this.campaign
      ? this.campanasService.updateCampaign(this.campaign.id, payload)
      : this.campanasService.createCampaign(payload);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.campaignSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar la campaña.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
