import { Component, OnInit, inject, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { AdminAcademicService, PadresContenidoItem } from '../../../core/services/admin-academic.service';
import { ContentModalComponent } from '../smart-cards/components/content-modal/content-modal.component';

@Component({
  selector: 'app-aprendamos-juntos',
  standalone: true,
  imports: [FormsModule, ContentModalComponent],
  templateUrl: './aprendamos-juntos.component.html',
})
export class AprendamosJuntosComponent implements OnInit {
  public academicService = inject(AdminAcademicService);

  public search = signal<string>('');
  public selectedCategory = signal<string>('');
  public selectedStatus = signal<string>('');
  public currentPage = signal<number>(1);

  public isModalOpen = signal<boolean>(false);
  public selectedItem = signal<PadresContenidoItem | null>(null);

  ngOnInit(): void {
    this.loadContents();
  }

  public loadContents(page: number = 1): void {
    this.currentPage.set(page);
    this.academicService.getPadresContenidos('aprendamos_juntos', {
      search: this.search(),
      category: this.selectedCategory(),
      status: this.selectedStatus(),
      page: this.currentPage(),
    }).subscribe();
  }

  public onSearchChange(term: string): void {
    this.search.set(term);
    this.loadContents(1);
  }

  public onCategoryChange(cat: string): void {
    this.selectedCategory.set(cat);
    this.loadContents(1);
  }

  public onStatusChange(stat: string): void {
    this.selectedStatus.set(stat);
    this.loadContents(1);
  }

  public openCreateModal(): void {
    this.selectedItem.set(null);
    this.isModalOpen.set(true);
  }

  public openEditModal(item: PadresContenidoItem): void {
    this.selectedItem.set(item);
    this.isModalOpen.set(true);
  }

  public closeModal(): void {
    this.isModalOpen.set(false);
    this.selectedItem.set(null);
  }

  public toggleStatus(item: PadresContenidoItem): void {
    this.academicService.togglePadresContenidoStatus(item.id).subscribe({
      next: () => this.loadContents(this.currentPage()),
    });
  }

  public deleteItem(item: PadresContenidoItem): void {
    if (confirm(`¿Estás seguro de eliminar el recurso pedagógico "${item.title}"?`)) {
      this.academicService.deletePadresContenido(item.id).subscribe({
        next: () => this.loadContents(this.currentPage()),
      });
    }
  }
}
