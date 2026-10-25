import { Component, EventEmitter, Input, Output, inject, signal } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { AdminAcademicService, EmailTemplateItem } from '../../../../../core/services/admin-academic.service';

@Component({
  selector: 'app-template-modal',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './template-modal.component.html',
})
export class TemplateModalComponent {
  private academicService = inject(AdminAcademicService);

  @Input() isOpen = false;
  @Input() set template(val: EmailTemplateItem | null) {
    this._template = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get template(): EmailTemplateItem | null {
    return this._template;
  }

  @Output() close = new EventEmitter<void>();
  @Output() templateSaved = new EventEmitter<void>();

  private _template: EmailTemplateItem | null = null;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  public form = {
    name: '',
    subject: '',
    body_html: '',
  };

  private initForm(t: EmailTemplateItem): void {
    this.form = {
      name: t.name,
      subject: t.subject,
      body_html: t.body_html,
    };
    this.errorMessage.set(null);
  }

  private resetForm(): void {
    this.form = {
      name: '',
      subject: '',
      body_html: '',
    };
    this.errorMessage.set(null);
  }

  public insertVariable(variable: string): void {
    this.form.body_html += ` ${variable} `;
  }

  public onSubmit(): void {
    if (!this.template) return;

    if (!this.form.name.trim() || !this.form.subject.trim() || !this.form.body_html.trim()) {
      this.errorMessage.set('Todos los campos son obligatorios.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    this.academicService.updateEmailTemplate(this.template.id, this.form).subscribe({
      next: () => {
        this.isSaving.set(false);
        this.templateSaved.emit();
        this.close.emit();
      },
      error: (err) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al actualizar la plantilla.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
