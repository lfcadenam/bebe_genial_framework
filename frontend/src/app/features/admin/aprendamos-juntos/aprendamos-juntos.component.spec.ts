import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AprendamosJuntosComponent } from './aprendamos-juntos.component';
import { AdminAcademicService, PadresContenidoItem } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('AprendamosJuntosComponent', () => {
  let component: AprendamosJuntosComponent;
  let fixture: ComponentFixture<AprendamosJuntosComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AprendamosJuntosComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(AprendamosJuntosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the aprendamos juntos component', () => {
    expect(component).toBeTruthy();
  });

  it('should open and close create modal', () => {
    component.openCreateModal();
    expect(component.selectedItem()).toBeNull();
    expect(component.isModalOpen()).toBeTrue();

    component.closeModal();
    expect(component.isModalOpen()).toBeFalse();
  });

  it('should open edit modal with parenting tip item', () => {
    const mockItem: PadresContenidoItem = {
      id: 8,
      section: 'aprendamos_juntos',
      category: 'Crianza Respetuosa',
      title: 'Cómo gestionar las pataletas',
      resource_type: 'article',
      order_index: 2,
      allow_download: false,
      is_active: true,
      created_at: '2026-01-01',
    };
    component.openEditModal(mockItem);
    expect(component.selectedItem()).toEqual(mockItem);
    expect(component.isModalOpen()).toBeTrue();
  });
});
