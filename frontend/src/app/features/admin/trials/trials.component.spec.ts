import { ComponentFixture, TestBed } from '@angular/core/testing';
import { TrialsComponent } from './trials.component';
import { AdminAcademicService } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('TrialsComponent', () => {
  let component: TrialsComponent;
  let fixture: ComponentFixture<TrialsComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TrialsComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(TrialsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the trials component', () => {
    expect(component).toBeTruthy();
  });

  it('should open and close trial modal', () => {
    component.openModal();
    expect(component.isModalOpen()).toBeTrue();

    component.closeModal();
    expect(component.isModalOpen()).toBeFalse();
  });
});
