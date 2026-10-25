import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, EmailTemplateItem } from '../../../core/services/admin-academic.service';
import { TemplateModalComponent } from './components/template-modal/template-modal.component';

@Component({
  selector: 'app-plantillas-correo',
  standalone: true,
  imports: [CommonModule, FormsModule, TemplateModalComponent],
  templateUrl: './plantillas-correo.component.html',
})
export class PlantillasCorreoComponent implements OnInit {
  public academicService = inject(AdminAcademicService);

  public isModalOpen = signal<boolean>(false);
  public selectedTemplate = signal<EmailTemplateItem | null>(null);

  ngOnInit(): void {
    this.loadTemplates();
  }

  public loadTemplates(): void {
    this.academicService.getEmailTemplates().subscribe();
  }

  public openEditModal(tmpl: EmailTemplateItem): void {
    this.selectedTemplate.set(tmpl);
    this.isModalOpen.set(true);
  }

  public closeModal(): void {
    this.isModalOpen.set(false);
    this.selectedTemplate.set(null);
  }
}
