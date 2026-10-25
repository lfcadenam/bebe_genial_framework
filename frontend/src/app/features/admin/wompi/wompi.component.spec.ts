import { ComponentFixture, TestBed } from '@angular/core/testing';
import { WompiComponent } from './wompi.component';
import { AdminCommercialService } from '../../../core/services/admin-commercial.service';
import { provideHttpClient } from '@angular/common/http';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';

describe('WompiComponent', () => {
  let component: WompiComponent;
  let fixture: ComponentFixture<WompiComponent>;
  let commercialService: AdminCommercialService;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [WompiComponent],
      providers: [
        provideHttpClient(),
        provideHttpClientTesting(),
        provideRouter([]),
      ],
    }).compileComponents();

    commercialService = TestBed.inject(AdminCommercialService);
    fixture = TestBed.createComponent(WompiComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create the wompi component', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize on planes tab by default', () => {
    expect(component.activeTab()).toBe('planes');
  });

  it('should switch tabs properly', () => {
    spyOn(component, 'loadSubscriptions');
    component.setTab('transacciones');
    expect(component.activeTab()).toBe('transacciones');
    expect(component.loadSubscriptions).toHaveBeenCalled();
  });

  it('should open new plan modal', () => {
    component.openNewPlan();
    expect(component.selectedPlan()).toBeNull();
    expect(component.isPlanModalOpen()).toBeTrue();
  });

  it('should open edit plan modal with existing plan', () => {
    const mockPlan = {
      id: 5,
      name: 'Plan Anual Premium',
      type: 'recurring' as const,
      price: '120000',
      currency: 'COP',
      duration_months: 12,
      trial_days: 0,
      is_active: true,
      benefits: 'Todos los cursos',
      related_courses: [1],
      subscriptions_count: 3,
      created_at: '2026-01-01',
    };
    component.openEditPlan(mockPlan);
    expect(component.selectedPlan()).toEqual(mockPlan);
    expect(component.isPlanModalOpen()).toBeTrue();
  });

  it('should close plan modal', () => {
    component.isPlanModalOpen.set(true);
    component.closePlanModal();
    expect(component.isPlanModalOpen()).toBeFalse();
  });
});
