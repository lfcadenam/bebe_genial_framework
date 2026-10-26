import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CommercialPartner } from '../../../../../core/services/admin-commercial.service';

@Component({
  selector: 'app-partner-password-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './partner-password-modal.component.html',
})
export class PartnerPasswordModalComponent {
  private commercialService = inject(AdminCommercialService);

  @Input() isOpen = false;
  @Input() set partner(val: CommercialPartner | null) {
    this._partner = val;
    this.resetForm();
  }
  get partner(): CommercialPartner | null {
    return this._partner;
  }

  @Output() close = new EventEmitter<void>();
  @Output() passwordReset = new EventEmitter<string>();

  private _partner: CommercialPartner | null = null;
  public newPassword = '';
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public generateRandomPassword(): void {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$*';
    let pass = '';
    for (let i = 0; i < 10; i++) {
      pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    this.newPassword = pass;
    this.errorMessage.set(null);
  }

  public onSubmit(): void {
    if (!this.newPassword || this.newPassword.length < 6) {
      this.errorMessage.set('La nueva contraseña debe tener mínimo 6 caracteres.');
      return;
    }

    if (!this.partner) return;

    this.isSaving.set(true);
    this.errorMessage.set(null);

    this.commercialService.resetPartnerPassword(this.partner.id, this.newPassword).subscribe({
      next: (res) => {
        this.isSaving.set(false);
        this.passwordReset.emit(this.newPassword);
        this.onClose();
      },
      error: (err) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al restablecer la contraseña.');
      },
    });
  }

  public resetForm(): void {
    this.newPassword = '';
    this.errorMessage.set(null);
    this.isSaving.set(false);
  }

  public onClose(): void {
    this.resetForm();
    this.close.emit();
  }
}
