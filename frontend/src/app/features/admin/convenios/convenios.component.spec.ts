import { ComponentFixture, TestBed } from '@angular/core/testing';
import { ConveniosComponent } from './convenios.component';
import { AdminCommercialService } from '../../../core/services/admin-commercial.service';
import { AdminUsersService } from '../../../core/services/admin-users.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';

describe('ConveniosComponent', () => {
  let component: ConveniosComponent;
  let fixture: ComponentFixture<ConveniosComponent>;
  let commercialService: AdminCommercialService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ConveniosComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    commercialService = TestBed.inject(AdminCommercialService);
    const usersService = TestBed.inject(AdminUsersService);
    spyOn(commercialService, 'getPartners').and.returnValue(of({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } }));
    spyOn(usersService, 'getCourses').and.returnValue(of({ success: true, data: [] }));

    fixture = TestBed.createComponent(ConveniosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the convenios component', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default filters and page 1', () => {
    expect(component.currentPage()).toBe(1);
    expect(component.typeFilter()).toBe('');
    expect(component.statusFilter()).toBe('');
    expect(component.searchTerm()).toBe('');
  });

  it('should update type filter and reload partners', () => {
    spyOn(component, 'loadPartners');
    component.currentPage.set(2);
    component.setTypeFilter('agreement');
    expect(component.typeFilter()).toBe('agreement');
    expect(component.currentPage()).toBe(1);
    expect(component.loadPartners).toHaveBeenCalled();
  });

  it('should update status filter and reload partners', () => {
    spyOn(component, 'loadPartners');
    component.setStatusFilter('active');
    expect(component.statusFilter()).toBe('active');
    expect(component.loadPartners).toHaveBeenCalled();
  });

  it('should open new partner drawer and clear selection', () => {
    component.openNewPartner();
    expect(component.selectedPartner()).toBeNull();
    expect(component.isDrawerOpen()).toBeTrue();
  });

  it('should open edit partner drawer with selected partner', () => {
    const mockPartner = {
      id: 10,
      company_name: 'Colegio ABC',
      contact_name: 'Director Juan',
      contact_email: 'juan@abc.edu',
      contact_phone: '3001234567',
      is_agreement: true,
      allowed_courses: [1, 2],
      allowed_months: 12,
      status: 'active' as const,
      students_count: 5,
      created_at: '2026-01-01',
    };
    component.openEditPartner(mockPartner);
    expect(component.selectedPartner()).toEqual(mockPartner);
    expect(component.isDrawerOpen()).toBeTrue();
  });

  it('should close drawer and students modal', () => {
    component.isDrawerOpen.set(true);
    component.closeDrawer();
    expect(component.isDrawerOpen()).toBeFalse();

    component.isStudentsModalOpen.set(true);
    component.closeStudentsModal();
    expect(component.isStudentsModalOpen()).toBeFalse();
  });
});
