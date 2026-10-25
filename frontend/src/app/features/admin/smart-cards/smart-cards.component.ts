import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, PadresContenidoItem } from '../../../core/services/admin-academic.service';
import { ContentModalComponent } from './components/content-modal/content-modal.component';

@Component({
  selector: 'app-smart-cards',
  standalone: true,
  imports: [CommonModule, FormsModule, ContentModalComponent],
  templateUrl: './smart-cards.component.html',
})
export class SmartCardsComponent implements OnInit {
  public academicService = inject(AdminAcademicService);

  public search = signal<string>('');
  public selectedCategory = signal<string>('');
  public selectedStatus = signal<string>('');
  public currentPage = signal<number>(1);

  public isModalOpen = signal<boolean>(false);
  public selectedItem = signal<PadresContenidoItem | null>(null);

  ngOnInit(): void {
    this.loadCards();
  }

  public loadCards(page: number = 1): void {
    this.currentPage.set(page);
    this.academicService.getPadresContenidos('smart_cards', {
      search: this.search(),
      category: this.selectedCategory(),
      status: this.selectedStatus(),
      page: this.currentPage(),
    }).subscribe();
  }

  public onSearchChange(term: string): void {
    this.search.set(term);
    this.loadCards(1);
  }

  public onCategoryChange(cat: string): void {
    this.selectedCategory.set(cat);
    this.loadCards(1);
  }

  public onStatusChange(stat: string): void {
    this.selectedStatus.set(stat);
    this.loadCards(1);
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
      next: () => this.loadCards(this.currentPage()),
    });
  }

  public deleteItem(item: PadresContenidoItem): void {
    if (confirm(`¿Estás seguro de eliminar el recurso "${item.title}"?`)) {
      this.academicService.deletePadresContenido(item.id).subscribe({
        next: () => this.loadCards(this.currentPage()),
      });
    }
  }
}
