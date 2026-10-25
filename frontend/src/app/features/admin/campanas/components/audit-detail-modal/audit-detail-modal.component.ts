import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiAuditLog } from '../../../../../core/services/admin-api-campanas.service';

@Component({
  selector: 'app-audit-detail-modal',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './audit-detail-modal.component.html',
})
export class AuditDetailModalComponent {
  @Input() isOpen = false;
  @Input() log: ApiAuditLog | null = null;
  @Output() close = new EventEmitter<void>();

  public formatJson(data: any): string {
    if (!data) return 'Sin contenido.';
    if (typeof data === 'string') {
      try {
        return JSON.stringify(JSON.parse(data), null, 2);
      } catch {
        return data;
      }
    }
    return JSON.stringify(data, null, 2);
  }

  public onClose(): void {
    this.close.emit();
  }
}
