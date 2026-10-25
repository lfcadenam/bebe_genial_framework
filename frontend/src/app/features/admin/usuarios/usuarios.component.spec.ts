import { ComponentFixture, TestBed } from '@angular/core/testing';
import { UsuariosComponent } from './usuarios.component';
import { AdminUsersService } from '../../../core/services/admin-users.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';

describe('UsuariosComponent', () => {
  let component: UsuariosComponent;
  let fixture: ComponentFixture<UsuariosComponent>;
  let usersService: AdminUsersService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [UsuariosComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    usersService = TestBed.inject(AdminUsersService);
    spyOn(usersService, 'getUsers').and.returnValue(of({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } } as any));
    spyOn(usersService, 'getCourses').and.returnValue(of({ success: true, data: [] }));
    fixture = TestBed.createComponent(UsuariosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the usuarios component', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default filters and page 1', () => {
    expect(component.currentPage()).toBe(1);
    expect(component.roleFilter()).toBe('');
    expect(component.statusFilter()).toBe('');
    expect(component.searchTerm()).toBe('');
  });

  it('should update role filter and reset page to 1', () => {
    component.currentPage.set(3);
    component.setRoleFilter('parent');
    expect(component.roleFilter()).toBe('parent');
    expect(component.currentPage()).toBe(1);
  });

  it('should open and close drawer', () => {
    const mockUser = {
      id: 1,
      first_name: 'Test',
      full_name: 'Test User',
      email: 'test@example.com',
      role: 'parent' as const,
      status: 'active' as const,
      must_change_password: false,
      created_at: '2026-01-01',
      total_enrollments: 1,
      active_enrollments: 1,
    };

    spyOn(usersService, 'getUserById').and.returnValue(of({ success: true, data: mockUser }));

    expect(component.isDrawerOpen()).toBeFalse();
    component.openDrawer(mockUser);
    expect(component.isDrawerOpen()).toBeTrue();
    expect(component.activeUser()).toBeTruthy();

    component.closeDrawer();
    expect(component.isDrawerOpen()).toBeFalse();
  });
});
