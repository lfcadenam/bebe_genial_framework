import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminCommercialService, CommercialPartner } from '../../../../../core/services/admin-commercial.service';

@Component({
  selector: 'app-partner-students-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
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
  public isExporting = signal<boolean>(false);
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

  public exportToExcel(): void {
    if (!this.partner) return;
    this.isExporting.set(true);
    this.commercialService.exportPartnerStudents(this.partner.id).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const sanitized = this.partner?.company_name.toLowerCase().replace(/[^a-z0-9]/g, '_') || 'aliado';
        a.download = `estudiantes_${sanitized}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        this.isExporting.set(false);
      },
      error: () => this.isExporting.set(false),
    });
  }

  public getBabyAge(birthDate?: string): string {
    if (!birthDate || birthDate === '0000-00-00' || birthDate === '1970-01-01') return 'No registrada';
    try {
      const dob = new Date(birthDate);
      const now = new Date();
      let years = now.getFullYear() - dob.getFullYear();
      let months = now.getMonth() - dob.getMonth();
      if (months < 0) {
        years--;
        months += 12;
      }
      if (years > 0) {
        return `${years} ${years === 1 ? 'año' : 'años'}${months > 0 && years < 5 ? ' y ' + months + ' m' : ''}`;
      }
      return months > 0 ? `${months} meses` : 'Menor de 1 mes';
    } catch {
      return birthDate;
    }
  }

  public isExpired(expiresAt?: string): boolean {
    if (!expiresAt) return true;
    return new Date(expiresAt).getTime() < Date.now();
  }

  public onClose(): void {
    this.close.emit();
  }
}
