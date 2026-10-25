import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminApiCampanasService, ApiAuditLog, ApiPartner, Campaign } from '../../../core/services/admin-api-campanas.service';
import { AdminUsersService } from '../../../core/services/admin-users.service';
import { PartnerModalComponent } from './components/partner-modal/partner-modal.component';
import { CampaignModalComponent } from './components/campaign-modal/campaign-modal.component';
import { AuditDetailModalComponent } from './components/audit-detail-modal/audit-detail-modal.component';

@Component({
  selector: 'app-campanas',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    PartnerModalComponent,
    CampaignModalComponent,
    AuditDetailModalComponent,
  ],
  templateUrl: './campanas.component.html',
})
export class CampanasComponent implements OnInit {
  public campanasService = inject(AdminApiCampanasService);
  public usersService = inject(AdminUsersService);

  // Pestaña Activa: 'aliados' | 'campanas' | 'auditoria'
  public activeTab = signal<'aliados' | 'campanas' | 'auditoria'>('aliados');

  // Filtros Aliados
  public partnerSearch = signal<string>('');
  public partnerStatusFilter = signal<string>('');
  public partnerPage = signal<number>(1);

  // Filtros Campañas
  public campaignSearch = signal<string>('');
  public campaignPartnerFilter = signal<number | undefined>(undefined);
  public campaignStatusFilter = signal<string>('');
  public campaignPage = signal<number>(1);

  // Filtros Auditoría
  public auditSearch = signal<string>('');
  public auditPartnerFilter = signal<number | undefined>(undefined);
  public auditStatusGroupFilter = signal<string>('');
  public auditFromDate = signal<string>('');
  public auditToDate = signal<string>('');
  public auditPage = signal<number>(1);

  // Modales
  public isPartnerModalOpen = signal<boolean>(false);
  public selectedPartner = signal<ApiPartner | null>(null);

  public isCampaignModalOpen = signal<boolean>(false);
  public selectedCampaign = signal<Campaign | null>(null);

  public isAuditDetailModalOpen = signal<boolean>(false);
  public selectedAuditLog = signal<ApiAuditLog | null>(null);

  // Toast Notificaciones
  public flashMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.loadPartners();
    this.usersService.getCourses().subscribe();
  }

  public setTab(tab: 'aliados' | 'campanas' | 'auditoria'): void {
    this.activeTab.set(tab);
    if (tab === 'campanas' && !this.campanasService.campaigns().length) {
      this.loadCampaigns();
    } else if (tab === 'auditoria') {
      this.loadAuditMetrics();
      this.loadAuditLogs();
    }
  }

  // =================== PARTNERS ===================
  public loadPartners(): void {
    this.campanasService
      .getPartners({
        search: this.partnerSearch(),
        status: this.partnerStatusFilter() || undefined,
        page: this.partnerPage(),
        per_page: 15,
      })
      .subscribe();
  }

  public onPartnerSearchChange(term: string): void {
    this.partnerSearch.set(term);
    this.partnerPage.set(1);
    this.loadPartners();
  }

  public setPartnerStatusFilter(status: string): void {
    this.partnerStatusFilter.set(status);
    this.partnerPage.set(1);
    this.loadPartners();
  }

  public onPartnerPageChange(page: number): void {
    if (page >= 1 && page <= this.campanasService.partnersMeta().last_page) {
      this.partnerPage.set(page);
      this.loadPartners();
    }
  }

  public openNewPartner(): void {
    this.selectedPartner.set(null);
    this.isPartnerModalOpen.set(true);
  }

  public openEditPartner(partner: ApiPartner): void {
    this.selectedPartner.set(partner);
    this.isPartnerModalOpen.set(true);
  }

  public closePartnerModal(): void {
    this.isPartnerModalOpen.set(false);
  }

  public onPartnerSaved(): void {
    this.loadPartners();
    this.showFlash('Aliado comercial guardado correctamente.');
  }

  public onTogglePartnerStatus(partner: ApiPartner): void {
    this.campanasService.togglePartnerStatus(partner.id).subscribe({
      next: () => {
        this.loadPartners();
        this.showFlash(`Estado del aliado "${partner.name}" actualizado.`);
      },
      error: () => this.showFlash('Error al actualizar el estado del aliado.'),
    });
  }

  public onRegenerateKey(partner: ApiPartner): void {
    if (!confirm(`¿Estás seguro de regenerar la API Key de "${partner.name}"? La clave anterior dejará de funcionar inmediatamente.`)) {
      return;
    }

    this.campanasService.regeneratePartnerKey(partner.id).subscribe({
      next: (res) => {
        this.loadPartners();
        this.showFlash(`Nueva API Key generada para "${partner.name}".`);
      },
      error: () => this.showFlash('Error al regenerar la API Key.'),
    });
  }

  public onDeletePartner(partner: ApiPartner): void {
    if (!confirm(`¿Estás seguro de eliminar el aliado "${partner.name}"?`)) {
      return;
    }

    this.campanasService.deletePartner(partner.id).subscribe({
      next: () => {
        this.loadPartners();
        this.showFlash(`Aliado "${partner.name}" eliminado correctamente.`);
      },
      error: (err: any) => {
        this.showFlash(err.error?.message || 'Error al eliminar el aliado.');
      },
    });
  }

  public copyApiKey(key: string): void {
    navigator.clipboard.writeText(key).then(() => {
      this.showFlash('API Key copiada al portapapeles.');
    });
  }

  // =================== CAMPAIGNS ===================
  public loadCampaigns(): void {
    this.campanasService
      .getCampaigns({
        partner_id: this.campaignPartnerFilter(),
        status: this.campaignStatusFilter() || undefined,
        search: this.campaignSearch(),
        page: this.campaignPage(),
        per_page: 15,
      })
      .subscribe();
  }

  public onCampaignSearchChange(term: string): void {
    this.campaignSearch.set(term);
    this.campaignPage.set(1);
    this.loadCampaigns();
  }

  public setCampaignPartnerFilter(partnerIdStr: string): void {
    const pId = partnerIdStr ? Number(partnerIdStr) : undefined;
    this.campaignPartnerFilter.set(pId);
    this.campaignPage.set(1);
    this.loadCampaigns();
  }

  public setCampaignStatusFilter(status: string): void {
    this.campaignStatusFilter.set(status);
    this.campaignPage.set(1);
    this.loadCampaigns();
  }

  public onCampaignPageChange(page: number): void {
    if (page >= 1 && page <= this.campanasService.campaignsMeta().last_page) {
      this.campaignPage.set(page);
      this.loadCampaigns();
    }
  }

  public openNewCampaign(): void {
    this.selectedCampaign.set(null);
    this.isCampaignModalOpen.set(true);
  }

  public openEditCampaign(campaign: Campaign): void {
    this.selectedCampaign.set(campaign);
    this.isCampaignModalOpen.set(true);
  }

  public closeCampaignModal(): void {
    this.isCampaignModalOpen.set(false);
  }

  public onCampaignSaved(): void {
    this.loadCampaigns();
    this.showFlash('Campaña guardada correctamente.');
  }

  public onToggleCampaignStatus(campaign: Campaign): void {
    this.campanasService.toggleCampaignStatus(campaign.id).subscribe({
      next: () => {
        this.loadCampaigns();
        this.showFlash(`Estado de la campaña "${campaign.code}" modificado.`);
      },
      error: () => this.showFlash('Error al cambiar estado de la campaña.'),
    });
  }

  public onDeleteCampaign(campaign: Campaign): void {
    if (!confirm(`¿Estás seguro de eliminar la campaña "${campaign.code}"?`)) {
      return;
    }

    this.campanasService.deleteCampaign(campaign.id).subscribe({
      next: () => {
        this.loadCampaigns();
        this.showFlash(`Campaña "${campaign.code}" eliminada.`);
      },
      error: (err: any) => {
        this.showFlash(err.error?.message || 'Error al eliminar la campaña.');
      },
    });
  }

  // =================== AUDITORÍA ===================
  public loadAuditMetrics(): void {
    this.campanasService.getAuditMetrics().subscribe();
  }

  public loadAuditLogs(): void {
    this.campanasService
      .getAuditLogs({
        partner_id: this.auditPartnerFilter(),
        status_group: this.auditStatusGroupFilter() || undefined,
        from_date: this.auditFromDate() || undefined,
        to_date: this.auditToDate() || undefined,
        search: this.auditSearch(),
        page: this.auditPage(),
        per_page: 20,
      })
      .subscribe();
  }

  public onAuditSearchChange(term: string): void {
    this.auditSearch.set(term);
    this.auditPage.set(1);
    this.loadAuditLogs();
  }

  public setAuditPartnerFilter(partnerIdStr: string): void {
    const pId = partnerIdStr ? Number(partnerIdStr) : undefined;
    this.auditPartnerFilter.set(pId);
    this.auditPage.set(1);
    this.loadAuditLogs();
  }

  public setAuditStatusFilter(group: string): void {
    this.auditStatusGroupFilter.set(group);
    this.auditPage.set(1);
    this.loadAuditLogs();
  }

  public onAuditDateChange(): void {
    this.auditPage.set(1);
    this.loadAuditLogs();
  }

  public onAuditPageChange(page: number): void {
    if (page >= 1 && page <= this.campanasService.auditLogsMeta().last_page) {
      this.auditPage.set(page);
      this.loadAuditLogs();
    }
  }

  public openAuditDetail(log: ApiAuditLog): void {
    this.selectedAuditLog.set(log);
    this.isAuditDetailModalOpen.set(true);
  }

  public closeAuditDetail(): void {
    this.isAuditDetailModalOpen.set(false);
  }

  private showFlash(msg: string): void {
    this.flashMessage.set(msg);
    setTimeout(() => this.flashMessage.set(null), 4000);
  }
}
