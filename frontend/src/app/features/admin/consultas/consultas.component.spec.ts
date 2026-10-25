import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ConsultasComponent } from './consultas.component';
import { AdminAcademicService } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('ConsultasComponent', () => {
  let component: ConsultasComponent;
  let fixture: ComponentFixture<ConsultasComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ConsultasComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(ConsultasComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the consultas component', () => {
    expect(component).toBeTruthy();
  });

  it('should update search term on input', () => {
    component.onSearchInput('bebe');
    expect(component.searchTerm()).toBe('bebe');
  });

  it('should clear search and results', () => {
    component.onSearchInput('prueba');
    academicService.omniResults.set([{ id: 1, name: 'Juan' }]);
    component.clearSearch();
    expect(component.searchTerm()).toBe('');
    expect(academicService.omniResults()).toEqual([]);
  });
});
