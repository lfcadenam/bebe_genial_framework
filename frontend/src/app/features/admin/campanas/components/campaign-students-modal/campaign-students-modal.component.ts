import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminApiCampanasService, Campaign, CampaignStudent, CampaignStudentsResponse } from '../../../../../core/services/admin-api-campanas.service';

@Component({
  selector: 'app-campaign-students-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './campaign-students-modal.component.html',
})
export class CampaignStudentsModalComponent {
  private campanasService = inject(AdminApiCampanasService);

  @Input() isOpen = false;
  @Input() set campaign(val: Campaign | null) {
    this._campaign = val;
    if (val && this.isOpen) {
      this.currentPage.set(1);
      this.searchQuery.set('');
      this.loadStudents();
    }
  }
  get campaign(): Campaign | null {
    return this._campaign;
  }

  @Output() close = new EventEmitter<void>();

  private _campaign: Campaign | null = null;
  public students = signal<CampaignStudent[]>([]);
  public campaignInfo = signal<any>(null);
  public isLoading = signal<boolean>(false);
  public isExporting = signal<boolean>(false);
  public searchQuery = signal<string>('');
  public currentPage = signal<number>(1);
  public totalItems = signal<number>(0);
  public lastPage = signal<number>(1);

  public loadStudents(): void {
    if (!this.campaign) return;

    this.isLoading.set(true);
    this.campanasService
      .getCampaignStudents(this.campaign.id, {
        page: this.currentPage(),
        per_page: 15,
        search: this.searchQuery().trim(),
      })
      .subscribe({
        next: (res: CampaignStudentsResponse) => {
          this.students.set(res.data);
          this.campaignInfo.set(res.campaign);
          this.totalItems.set(res.meta.total);
          this.lastPage.set(res.meta.last_page);
          this.isLoading.set(false);
        },
        error: () => {
          this.isLoading.set(false);
        },
      });
  }

  public onSearchChange(term: string): void {
    this.searchQuery.set(term);
    this.currentPage.set(1);
    this.loadStudents();
  }

  public onPageChange(page: number): void {
    if (page >= 1 && page <= this.lastPage()) {
      this.currentPage.set(page);
      this.loadStudents();
    }
  }

  public exportCsv(): void {
    if (!this.campaign) return;
    this.isExporting.set(true);

    this.campanasService.exportCampaignStudents(this.campaign.id).subscribe({
      next: (blob: Blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `alumnos_campana_${this.campaign?.code || 'b2b'}_${new Date().toISOString().substring(0, 10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        this.isExporting.set(false);
      },
      error: () => {
        this.isExporting.set(false);
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
