import { ComponentFixture, TestBed } from '@angular/core/testing';
import { SmartCardsComponent } from './smart-cards.component';
import { AdminAcademicService, PadresContenidoItem } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('SmartCardsComponent', () => {
  let component: SmartCardsComponent;
  let fixture: ComponentFixture<SmartCardsComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [SmartCardsComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(SmartCardsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the smart cards component', () => {
    expect(component).toBeTruthy();
  });

  it('should open create modal with empty selection', () => {
    component.openCreateModal();
    expect(component.selectedItem()).toBeNull();
    expect(component.isModalOpen()).toBeTrue();
  });

  it('should open edit modal with populated card', () => {
    const mockCard: PadresContenidoItem = {
      id: 5,
      section: 'smart_cards',
      category: 'Lógica',
      title: 'Ficha Figuras Geométricas',
      resource_type: 'video',
      order_index: 1,
      allow_download: true,
      is_active: true,
      created_at: '2026-01-01',
    };
    component.openEditModal(mockCard);
    expect(component.selectedItem()).toEqual(mockCard);
    expect(component.isModalOpen()).toBeTrue();
  });

  it('should close modal', () => {
    component.openCreateModal();
    component.closeModal();
    expect(component.isModalOpen()).toBeFalse();
    expect(component.selectedItem()).toBeNull();
  });
});
