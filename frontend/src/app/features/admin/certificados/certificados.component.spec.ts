import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CertificadosComponent } from './certificados.component';
import { AdminAcademicService } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('CertificadosComponent', () => {
  let component: CertificadosComponent;
  let fixture: ComponentFixture<CertificadosComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CertificadosComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(CertificadosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the certificados component', () => {
    expect(component).toBeTruthy();
  });

  it('should open and close certificate modal', () => {
    component.openModal();
    expect(component.isModalOpen()).toBeTrue();

    component.closeModal();
    expect(component.isModalOpen()).toBeFalse();
  });
});
