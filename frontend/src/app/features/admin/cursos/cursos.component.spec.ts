import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CursosComponent } from './cursos.component';
import { AdminAcademicService } from '../../../core/services/admin-academic.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('CursosComponent', () => {
  let component: CursosComponent;
  let fixture: ComponentFixture<CursosComponent>;
  let academicService: AdminAcademicService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CursosComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    academicService = TestBed.inject(AdminAcademicService);
    fixture = TestBed.createComponent(CursosComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the cursos component', () => {
    expect(component).toBeTruthy();
  });

  it('should open and close drawer for new course', () => {
    component.openNewCourse();
    expect(component.selectedCourse()).toBeNull();
    expect(component.isDrawerOpen()).toBeTrue();

    component.closeDrawer();
    expect(component.isDrawerOpen()).toBeFalse();
  });

  it('should open drawer with course data on edit', () => {
    const mockCourse = {
      id: 1,
      title: 'Método Bebé Políglota',
      is_visible: true,
      is_active: true,
      hours: 45,
      price: 150000,
    };
    component.openEditCourse(mockCourse);
    expect(component.selectedCourse()).toEqual(mockCourse);
    expect(component.isDrawerOpen()).toBeTrue();
  });
});
