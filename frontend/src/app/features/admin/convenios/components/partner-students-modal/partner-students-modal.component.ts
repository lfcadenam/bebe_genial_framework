import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CommercialPartner } from '../../../../../core/services/admin-commercial.service';

@Component({
  selector: 'app-partner-students-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './partner-students-modal.component.html',
})
export class PartnerStudentsModalComponent {
  private commercialService = inject(AdminCommercialService);

  @Input() isOpen = false;
  @Input() set partner(val: CommercialPartner | null) {
    this._partner = val;
    if (val) {
      this.loadStudents(val.id);
    }
  }
  get partner(): CommercialPartner | null {
    return this._partner;
  }

  @Output() close = new EventEmitter<void>();

  private _partner: CommercialPartner | null = null;
  public students = signal<any[]>([]);
  public meta = signal<any>({ current_page: 1, last_page: 1, total: 0 });
  public isLoading = signal<boolean>(false);
  public searchTerm = signal<string>('');

  public loadStudents(partnerId: number, page = 1): void {
    this.isLoading.set(true);
    this.commercialService
      .getPartnerStudents(partnerId, { search: this.searchTerm(), page })
      .subscribe({
        next: (res) => {
          this.students.set(res.data);
          this.meta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      });
  }

  public onSearchChange(term: string): void {
    this.searchTerm.set(term);
    if (this.partner) {
      this.loadStudents(this.partner.id, 1);
    }
  }

  public onPageChange(page: number): void {
    if (this.partner && page >= 1 && page <= this.meta().last_page) {
      this.loadStudents(this.partner.id, page);
    }
  }

  public onClose(): void {
    this.close.emit();
  }
}
