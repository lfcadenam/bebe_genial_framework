import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface CommercialPartner {
  id: number;
  company_name: string;
  contact_name: string;
  contact_email: string;
  contact_phone?: string | null;
  is_agreement: boolean; // true: Convenio Institucional, false: Distribuidor
  status: 'active' | 'inactive' | 'suspended';
  allowed_courses?: number[] | null;
  allowed_months: number;
  notes?: string | null;
  students_count?: number;
  created_at: string;
}

export interface WompiPlan {
  id: number;
  name: string;
  type: 'single' | 'recurring';
  price: string | number;
  currency: string;
  duration_months: number;
  trial_days: number;
  is_active: boolean;
  benefits?: string | null;
  related_courses?: number[] | null;
  subscriptions_count?: number;
  active_subscriptions_count?: number;
  created_at: string;
}

export interface WompiSubscription {
  id: number;
  user_id: number;
  plan_id: number;
  wompi_transaction_id?: string | null;
  reference: string;
  amount: string | number;
  currency: string;
  status: 'APPROVED' | 'DECLINED' | 'PENDING' | 'VOIDED' | 'ERROR';
  payment_method?: string | null;
  starts_at?: string | null;
  expires_at?: string | null;
  created_at: string;
  user?: {
    id: number;
    first_name: string;
    last_name?: string;
    email: string;
    phone?: string;
  };
  plan?: {
    id: number;
    name: string;
    price: string | number;
    type: string;
  };
}

export interface WompiMetrics {
  total_revenue: number;
  formatted_revenue: string;
  approved_count: number;
  declined_count: number;
  pending_count: number;
  total_transactions: number;
  active_plans: number;
  success_rate: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface CreatePartnerPayload {
  company_name: string;
  contact_name: string;
  contact_email: string;
  contact_phone?: string;
  is_agreement: boolean;
  status: string;
  allowed_courses?: number[];
  allowed_months: number;
  notes?: string;
}

export interface CreatePlanPayload {
  name: string;
  type: 'single' | 'recurring';
  price: number;
  currency?: string;
  duration_months: number;
  trial_days?: number;
  is_active?: boolean;
  benefits?: string;
  related_courses?: number[];
}

@Injectable({
  providedIn: 'root',
})
export class AdminCommercialService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api/v1/admin';

  // Signals reactivos
  public partners = signal<CommercialPartner[]>([]);
  public partnersMeta = signal<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  public partnersKpis = signal<{
    total_partners: number;
    total_agreements: number;
    total_distributors: number;
    active_partners: number;
  }>({
    total_partners: 0,
    total_agreements: 0,
    total_distributors: 0,
    active_partners: 0,
  });

  public wompiPlans = signal<WompiPlan[]>([]);
  public wompiSubscriptions = signal<WompiSubscription[]>([]);
  public wompiSubscriptionsMeta = signal<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  public wompiMetrics = signal<WompiMetrics | null>(null);

  public isLoading = signal<boolean>(false);

  // ==================== CONVENIOS Y DISTRIBUIDORES ====================

  getPartners(filters: { search?: string; status?: string; type?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);

    let params = new HttpParams();
    if (filters.search) params = params.set('search', filters.search);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.type) params = params.set('type', filters.type);
    if (filters.page) params = params.set('page', filters.page.toString());
    if (filters.per_page) params = params.set('per_page', filters.per_page.toString());

    return this.http.get<{ success: boolean; data: CommercialPartner[]; meta: PaginationMeta; kpis: any }>(
      `${this.apiUrl}/commercial-partners`,
      { params }
    ).pipe(
      tap({
        next: (res) => {
          this.partners.set(res.data);
          this.partnersMeta.set(res.meta);
          if (res.kpis) this.partnersKpis.set(res.kpis);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  getPartnerById(id: number): Observable<{ success: boolean; data: CommercialPartner }> {
    return this.http.get<{ success: boolean; data: CommercialPartner }>(`${this.apiUrl}/commercial-partners/${id}`);
  }

  createPartner(payload: CreatePartnerPayload): Observable<{ success: boolean; message: string; data: CommercialPartner }> {
    return this.http.post<{ success: boolean; message: string; data: CommercialPartner }>(
      `${this.apiUrl}/commercial-partners`,
      payload
    );
  }

  updatePartner(id: number, payload: CreatePartnerPayload): Observable<{ success: boolean; message: string; data: CommercialPartner }> {
    return this.http.put<{ success: boolean; message: string; data: CommercialPartner }>(
      `${this.apiUrl}/commercial-partners/${id}`,
      payload
    );
  }

  deletePartner(id: number): Observable<{ success: boolean; message: string }> {
    return this.http.delete<{ success: boolean; message: string }>(`${this.apiUrl}/commercial-partners/${id}`);
  }

  getPartnerStudents(id: number, filters: { search?: string; page?: number; per_page?: number } = {}): Observable<any> {
    let params = new HttpParams();
    if (filters.search) params = params.set('search', filters.search);
    if (filters.page) params = params.set('page', filters.page.toString());
    if (filters.per_page) params = params.set('per_page', filters.per_page.toString());

    return this.http.get(`${this.apiUrl}/commercial-partners/${id}/students`, { params });
  }

  // ==================== WOMPI (PLANES, TRANSACCIONES & MÉTRICAS) ====================

  getWompiMetrics(): Observable<{ success: boolean; data: WompiMetrics }> {
    return this.http.get<{ success: boolean; data: WompiMetrics }>(`${this.apiUrl}/wompi/metrics`).pipe(
      tap((res) => this.wompiMetrics.set(res.data))
    );
  }

  getWompiPlans(): Observable<{ success: boolean; data: WompiPlan[] }> {
    return this.http.get<{ success: boolean; data: WompiPlan[] }>(`${this.apiUrl}/wompi/plans`).pipe(
      tap((res) => this.wompiPlans.set(res.data))
    );
  }

  createWompiPlan(payload: CreatePlanPayload): Observable<{ success: boolean; message: string; data: WompiPlan }> {
    return this.http.post<{ success: boolean; message: string; data: WompiPlan }>(`${this.apiUrl}/wompi/plans`, payload);
  }

  updateWompiPlan(id: number, payload: CreatePlanPayload): Observable<{ success: boolean; message: string; data: WompiPlan }> {
    return this.http.put<{ success: boolean; message: string; data: WompiPlan }>(`${this.apiUrl}/wompi/plans/${id}`, payload);
  }

  toggleWompiPlanStatus(id: number): Observable<{ success: boolean; message: string; data: WompiPlan }> {
    return this.http.post<{ success: boolean; message: string; data: WompiPlan }>(
      `${this.apiUrl}/wompi/plans/${id}/toggle-status`,
      {}
    );
  }

  getWompiSubscriptions(filters: { search?: string; status?: string; plan_id?: number; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);

    let params = new HttpParams();
    if (filters.search) params = params.set('search', filters.search);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.plan_id) params = params.set('plan_id', filters.plan_id.toString());
    if (filters.page) params = params.set('page', filters.page.toString());
    if (filters.per_page) params = params.set('per_page', filters.per_page.toString());

    return this.http.get<{ success: boolean; data: WompiSubscription[]; meta: PaginationMeta }>(
      `${this.apiUrl}/wompi/subscriptions`,
      { params }
    ).pipe(
      tap({
        next: (res) => {
          this.wompiSubscriptions.set(res.data);
          this.wompiSubscriptionsMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }
}
