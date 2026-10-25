import { ComponentFixture, TestBed } from '@angular/core/testing';
import { PlantillasCorreoComponent } from './plantillas-correo.component';
import { AdminAcademicService, EmailTemplateItem } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('PlantillasCorreoComponent', () => {
  let component: PlantillasCorreoComponent;
  let fixture: ComponentFixture<PlantillasCorreoComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [PlantillasCorreoComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(PlantillasCorreoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the plantillas correo component', () => {
    expect(component).toBeTruthy();
  });

  it('should open edit modal with template item', () => {
    const mockTmpl: EmailTemplateItem = {
      id: 1,
      slug: 'welcome_parent',
      name: 'Bienvenida Familias',
      subject: '¡Bienvenidos a Bebé Genial!',
      body_html: '<h1>Hola {{name}}</h1>',
      available_variables: ['{{name}}', '{{email}}'],
      updated_at: '2026-01-01',
    };
    component.openEditModal(mockTmpl);
    expect(component.selectedTemplate()).toEqual(mockTmpl);
    expect(component.isModalOpen()).toBeTrue();
  });

  it('should close modal', () => {
    component.closeModal();
    expect(component.isModalOpen()).toBeFalse();
    expect(component.selectedTemplate()).toBeNull();
  });
});
