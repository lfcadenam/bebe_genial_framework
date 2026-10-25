import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, CourseTrialItem } from '../../../core/services/admin-academic.service';
import { TrialModalComponent } from './components/trial-modal/trial-modal.component';

@Component({
  selector: 'app-trials',
  standalone: true,
  imports: [CommonModule, FormsModule, TrialModalComponent],
  templateUrl: './trials.component.html',
})
export class TrialsComponent implements OnInit {
  public academicService = inject(AdminAcademicService);

  public search = signal<string>('');
  public selectedStatus = signal<string>('');
  public currentPage = signal<number>(1);

  public isModalOpen = signal<boolean>(false);

  ngOnInit(): void {
    this.loadTrials();
  }

  public loadTrials(page: number = 1): void {
    this.currentPage.set(page);
    this.academicService.getTrials({
      search: this.search(),
      status: this.selectedStatus(),
      page: this.currentPage(),
    }).subscribe();
  }

  public onSearchChange(term: string): void {
    this.search.set(term);
    this.loadTrials(1);
  }

  public onStatusChange(stat: string): void {
    this.selectedStatus.set(stat);
    this.loadTrials(1);
  }

  public openModal(): void {
    this.isModalOpen.set(true);
  }

  public closeModal(): void {
    this.isModalOpen.set(false);
  }

  public revokeTrial(trial: CourseTrialItem): void {
    if (confirm(`¿Estás seguro de revocar el acceso demo para ${trial.user?.full_name || trial.user?.email || 'este usuario'}?`)) {
      this.academicService.revokeTrial(trial.id).subscribe({
        next: () => this.loadTrials(this.currentPage()),
      });
    }
  }
}
