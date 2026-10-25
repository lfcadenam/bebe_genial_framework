import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, CertificateItem } from '../../../core/services/admin-academic.service';
import { CertificateModalComponent } from './components/certificate-modal/certificate-modal.component';

@Component({
  selector: 'app-certificados',
  standalone: true,
  imports: [CommonModule, FormsModule, CertificateModalComponent],
  templateUrl: './certificados.component.html',
})
export class CertificadosComponent implements OnInit {
  public academicService = inject(AdminAcademicService);

  public search = signal<string>('');
  public selectedStatus = signal<string>('');
  public currentPage = signal<number>(1);

  public isModalOpen = signal<boolean>(false);

  ngOnInit(): void {
    this.loadCertificates();
  }

  public loadCertificates(page: number = 1): void {
    this.currentPage.set(page);
    this.academicService.getCertificates({
      search: this.search(),
      status: this.selectedStatus(),
      page: this.currentPage(),
    }).subscribe();
  }

  public onSearchChange(term: string): void {
    this.search.set(term);
    this.loadCertificates(1);
  }

  public onStatusChange(stat: string): void {
    this.selectedStatus.set(stat);
    this.loadCertificates(1);
  }

  public openModal(): void {
    this.isModalOpen.set(true);
  }

  public closeModal(): void {
    this.isModalOpen.set(false);
  }

  public revokeCertificate(item: CertificateItem): void {
    if (confirm(`¿Estás seguro de revocar el diploma ${item.certificate_code}?`)) {
      this.academicService.revokeCertificate(item.id).subscribe({
        next: () => this.loadCertificates(this.currentPage()),
      });
    }
  }
}
