import { Component, EventEmitter, Input, Output, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CommercialPartner } from '../../../../../core/services/admin-commercial.service';

@Component({
  selector: 'app-partner-qr-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './partner-qr-modal.component.html',
})
export class PartnerQrModalComponent {
  @Input() isOpen = false;
  @Input() set partner(val: CommercialPartner | null) {
    this._partner = val;
    this.copied.set(false);
  }
  get partner(): CommercialPartner | null {
    return this._partner;
  }

  @Output() close = new EventEmitter<void>();

  private _partner: CommercialPartner | null = null;
  public copied = signal<boolean>(false);

  public get registrationUrl(): string {
    if (!this._partner) return '';
    const code = this._partner.qr_code || 'distribuidor';
    const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost:4200';
    return `${origin}/registro?dist=${code}`;
  }

  public get qrImageUrl(): string {
    if (!this.registrationUrl) return '';
    return `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(this.registrationUrl)}&margin=10`;
  }

  public copyLink(): void {
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
      navigator.clipboard.writeText(this.registrationUrl).then(() => {
        this.copied.set(true);
        setTimeout(() => this.copied.set(false), 3000);
      });
    }
  }

  public onClose(): void {
    this.copied.set(false);
    this.close.emit();
  }
}
