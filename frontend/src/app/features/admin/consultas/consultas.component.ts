import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subject, debounceTime, distinctUntilChanged } from 'rxjs';
import { AdminAcademicService } from '../../../core/services/admin-academic.service';

@Component({
  selector: 'app-consultas',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './consultas.component.html',
})
export class ConsultasComponent {
  public academicService = inject(AdminAcademicService);

  public searchTerm = signal<string>('');
  private searchSubject = new Subject<string>();

  constructor() {
    this.searchSubject
      .pipe(
        debounceTime(350),
        distinctUntilChanged()
      )
      .subscribe((query) => {
        if (query && query.trim().length >= 2) {
          this.academicService.omniSearch(query.trim()).subscribe();
        } else {
          this.academicService.omniResults.set([]);
        }
      });
  }

  public onSearchInput(term: string): void {
    this.searchTerm.set(term);
    this.searchSubject.next(term);
  }

  public clearSearch(): void {
    this.searchTerm.set('');
    this.academicService.omniResults.set([]);
  }
}
