import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, PadresContenidoItem } from '../../../../../core/services/admin-academic.service';

@Component({
  selector: 'app-content-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './content-modal.component.html',
})
export class ContentModalComponent {
  private academicService = inject(AdminAcademicService);

  @Input() section: 'smart_cards' | 'aprendamos_juntos' = 'smart_cards';
  @Input() isOpen = false;
  @Input() set content(val: PadresContenidoItem | null) {
    this._content = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get content(): PadresContenidoItem | null {
    return this._content;
  }

  @Output() close = new EventEmitter<void>();
  @Output() contentSaved = new EventEmitter<void>();

  private _content: PadresContenidoItem | null = null;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    category: '',
    title: '',
    resource_type: 'video' as 'video' | 'pdf' | 'article',
    url_video: '',
    file_path: '',
    description: '',
    order_index: 0,
    allow_download: false,
    is_active: true,
  };

  private initForm(c: PadresContenidoItem): void {
    this.form = {
      category: c.category || '',
      title: c.title,
      resource_type: c.resource_type,
      url_video: c.url_video || '',
      file_path: c.file_path || '',
      description: c.description || '',
      order_index: c.order_index || 0,
      allow_download: c.allow_download,
      is_active: c.is_active,
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    this.form = {
      category: this.section === 'smart_cards' ? 'Lógico Matemática' : 'Tips para Padres',
      title: '',
      resource_type: 'video',
      url_video: '',
      file_path: '',
      description: '',
      order_index: 0,
      allow_download: false,
      is_active: true,
    };
    this.errorMessage.set(null);
  }

  public onSubmit(): void {
    if (!this.form.title.trim()) {
      this.errorMessage.set('El título del recurso es obligatorio.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    const payload = {
      ...this.form,
      section: this.section,
      order_index: Number(this.form.order_index),
    };

    const action$ = this.content
      ? this.academicService.updatePadresContenido(this.content.id, payload)
      : this.academicService.createPadresContenido(payload);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.contentSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar el recurso.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
