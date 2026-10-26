import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminApiCampanasService, ApiPartner, CreatePartnerPayload } from '../../../../../core/services/admin-api-campanas.service';

@Component({
  selector: 'app-partner-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './partner-modal.component.html',
})
export class PartnerModalComponent {
  public campanasService = inject(AdminApiCampanasService);

  @Input() isOpen = false;
  @Input() set partner(val: ApiPartner | null) {
    this._partner = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get partner(): ApiPartner | null {
    return this._partner;
  }

  @Output() close = new EventEmitter<void>();
  @Output() partnerSaved = new EventEmitter<void>();

  private _partner: ApiPartner | null = null;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);
  public showAdvanced = signal<boolean>(false);

  public form: CreatePartnerPayload = {
    name: '',
    partner_code: '',
    contact_name: '',
    contact_email: '',
    ip_whitelist: '',
    ghl_access_token: '',
    etiqueta: '',
    status: 'active',
  };

  public get originCode(): number {
    return this.partner?.origin_code || this.form.origin_code || (this.campanasService.partnersMeta().next_origin_code || 1);
  }

  private initForm(partner: ApiPartner): void {
    this.form = {
      name: partner.name,
      partner_code: partner.partner_code,
      contact_name: partner.contact_name || '',
      contact_email: partner.contact_email || '',
      ip_whitelist: partner.ip_whitelist || '',
      ghl_access_token: partner.ghl_access_token || '',
      etiqueta: partner.etiqueta || '',
      status: partner.status,
      origin_code: partner.origin_code,
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    const nextCode = this.campanasService.partnersMeta().next_origin_code || 1;
    this.form = {
      name: '',
      partner_code: '',
      contact_name: '',
      contact_email: '',
      ip_whitelist: '',
      ghl_access_token: '',
      etiqueta: '',
      status: 'active',
      origin_code: nextCode,
    };
    this.errorMessage.set(null);
  }

  public onSubmit(): void {
    if (!this.form.name.trim() || !this.form.partner_code.trim()) {
      this.errorMessage.set('El nombre de la empresa y el código slug único son obligatorios.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    const action$ = this.partner
      ? this.campanasService.updatePartner(this.partner.id, this.form)
      : this.campanasService.createPartner(this.form);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.partnerSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar la plataforma B2B.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
