import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CampanasComponent } from './campanas.component';
import { AdminApiCampanasService } from '../../../core/services/admin-api-campanas.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('CampanasComponent', () => {
  let component: CampanasComponent;
  let fixture: ComponentFixture<CampanasComponent>;
  let campanasService: AdminApiCampanasService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CampanasComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    campanasService = TestBed.inject(AdminApiCampanasService);
    fixture = TestBed.createComponent(CampanasComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the campanas component', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize on aliados tab by default', () => {
    expect(component.activeTab()).toBe('aliados');
  });

  it('should switch tabs to campanas and auditoria properly', () => {
    spyOn(component, 'loadCampaigns');
    component.setTab('campanas');
    expect(component.activeTab()).toBe('campanas');
    expect(component.loadCampaigns).toHaveBeenCalled();

    spyOn(component, 'loadAuditLogs');
    component.setTab('auditoria');
    expect(component.activeTab()).toBe('auditoria');
    expect(component.loadAuditLogs).toHaveBeenCalled();
  });

  it('should open and close partner modal', () => {
    component.openNewPartner();
    expect(component.selectedPartner()).toBeNull();
    expect(component.isPartnerModalOpen()).toBeTrue();

    const mockPartner = {
      id: 1,
      origin_code: 1,
      name: 'Colsubsidio',
      partner_code: 'colsubsidio',
      api_key: 'bbg_live_test123',
      status: 'active' as const,
      created_at: '2026-01-01',
    };
    component.openEditPartner(mockPartner);
    expect(component.selectedPartner()).toEqual(mockPartner);

    component.closePartnerModal();
    expect(component.isPartnerModalOpen()).toBeFalse();
  });

  it('should open and close campaign modal', () => {
    component.openNewCampaign();
    expect(component.selectedCampaign()).toBeNull();
    expect(component.isCampaignModalOpen()).toBeTrue();

    const mockCampaign = {
      id: 10,
      course_id: 24,
      code: 'COLSUB_2026',
      name: 'Campaña Escolar Colsubsidio',
      book_limit: 5,
      duration_days: 60,
      starts_at: '2026-01-01',
      ends_at: '2026-12-31',
      is_active: true,
      created_at: '2026-01-01',
    };
    component.openEditCampaign(mockCampaign);
    expect(component.selectedCampaign()).toEqual(mockCampaign);

    component.closeCampaignModal();
    expect(component.isCampaignModalOpen()).toBeFalse();
  });

  it('should open and close audit detail modal', () => {
    const mockLog = {
      id: 100,
      endpoint: '/api/v1/leoconleo/registro',
      method: 'POST',
      http_status: 200,
      ip_address: '186.28.1.1',
      created_at: '2026-01-01',
    };
    component.openAuditDetail(mockLog);
    expect(component.selectedAuditLog()).toEqual(mockLog);
    expect(component.isAuditDetailModalOpen()).toBeTrue();

    component.closeAuditDetail();
    expect(component.isAuditDetailModalOpen()).toBeFalse();
  });
});
