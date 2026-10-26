import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { AdminCourse } from './admin-users.service';

export interface ApiPartner {
  id: number;
  origin_code: number;
  name: string;
  partner_code: string;
  api_key: string;
  ip_whitelist?: string | null;
  contact_name?: string | null;
  contact_email?: string | null;
  ghl_access_token?: string | null;
  etiqueta?: string | null;
  status: 'active' | 'inactive';
  campaigns_count?: number;
  audit_logs_count?: number;
  created_at: string;
}

export interface Campaign {
  id: number;
  partner_id?: number | null;
  partner?: ApiPartner | null;
  course_id: number;
  course?: AdminCourse | null;
  code: string;
  name: string;
  book_limit: number; // 0 = ilimitado, 365 = todos
  cuentos_permitidos?: number[] | null;
  duration_days: number;
  starts_at: string;
  ends_at: string;
  is_active: boolean;
  enrollments_count?: number;
  created_at: string;
}

export interface CuentoItem {
  id: number;
  category_id: number;
  title: string;
  page: number;
  slug?: string;
}

export interface CuentoCategory {
  id: number;
  name: string;
  image: string;
  image_url: string;
  total_cuentos: number;
  cuentos: CuentoItem[];
}

export interface CuentosCatalogResponse {
  success: boolean;
  total_stories: number;
  data: CuentoCategory[];
}

export interface CampaignStudent {
  id: number;
  user_id?: number | null;
  parent_name: string;
  email?: string | null;
  phone?: string | null;
  user_status: string;
  baby_name: string;
  baby_birth_date?: string | null;
  baby_age_text: string;
  access_type: string;
  book_limit: number;
  origin_channel: string;
  enrolled_at?: string | null;
  expires_at?: string | null;
  is_valid: boolean;
}

export interface CampaignStudentsResponse {
  success: boolean;
  campaign: {
    id: number;
    code: string;
    name: string;
    course_name: string;
    partner_name: string;
  };
  data: CampaignStudent[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ApiAuditLog {
  id: number;
  partner_id?: number | null;
  partner?: ApiPartner | null;
  campaign_code?: string | null;
  endpoint: string;
  method: string;
  http_status: number;
  ip_address: string;
  requested_email?: string | null;
  payload?: any;
  response?: any;
  execution_time_ms?: number | null;
  created_at: string;
}

export interface AuditMetrics {
  total_requests: number;
  total_successful: number;
  total_validation: number;
  total_rejected: number;
  success_rate: number;
}

export interface CreatePartnerPayload {
  name: string;
  partner_code: string;
  contact_name?: string | null;
  contact_email?: string | null;
  ip_whitelist?: string | null;
  ghl_access_token?: string | null;
  etiqueta?: string | null;
  status?: 'active' | 'inactive';
  origin_code?: number;
}

export interface CreateCampaignPayload {
  partner_id?: number | null;
  course_id: number;
  code: string;
  name: string;
  book_limit: number;
  duration_days: number;
  starts_at: string;
  ends_at: string;
  is_active?: boolean;
  cuentos_permitidos?: number[] | null;
  cuentos_seleccionados?: number[];
}

@Injectable({
  providedIn: 'root',
})
export class AdminApiCampanasService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api/v1/admin';

  // State Signals
  public partners = signal<ApiPartner[]>([]);
  public partnersMeta = signal<any>({ current_page: 1, last_page: 1, total: 0, next_origin_code: 1 });

  public campaigns = signal<Campaign[]>([]);
  public campaignsMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public auditLogs = signal<ApiAuditLog[]>([]);
  public auditLogsMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public auditMetrics = signal<AuditMetrics | null>(null);

  public isLoading = signal<boolean>(false);

  // =================== PARTNERS ===================
  public getPartners(filters: { search?: string; status?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams();
    if (filters.search) params = params.set('search', filters.search);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/api-partners`, { params }).pipe(
      tap({
        next: (res) => {
          this.partners.set(res.data);
          this.partnersMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public createPartner(payload: CreatePartnerPayload): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api-partners`, payload);
  }

  public updatePartner(id: number, payload: Partial<CreatePartnerPayload>): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/api-partners/${id}`, payload);
  }

  public togglePartnerStatus(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api-partners/${id}/toggle-status`, {});
  }

  public regeneratePartnerKey(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/api-partners/${id}/regenerate-key`, {});
  }

  public deletePartner(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/api-partners/${id}`);
  }

  // =================== CAMPAIGNS ===================
  public getCampaigns(filters: { partner_id?: number; status?: string; search?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams();
    if (filters.partner_id) params = params.set('partner_id', filters.partner_id);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/campaigns`, { params }).pipe(
      tap({
        next: (res) => {
          this.campaigns.set(res.data);
          this.campaignsMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public getCuentosCatalog(): Observable<CuentosCatalogResponse> {
    return this.http.get<CuentosCatalogResponse>(`${this.apiUrl}/campaigns/cuentos-catalog`);
  }

  public getCampaignStudents(campaignId: number, filters: { page?: number; per_page?: number; search?: string } = {}): Observable<CampaignStudentsResponse> {
    let params = new HttpParams();
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);
    if (filters.search) params = params.set('search', filters.search);

    return this.http.get<CampaignStudentsResponse>(`${this.apiUrl}/campaigns/${campaignId}/students`, { params });
  }

  public exportCampaignStudents(campaignId: number): Observable<Blob> {
    return this.http.get(`${this.apiUrl}/campaigns/${campaignId}/export-students`, {
      responseType: 'blob',
    });
  }

  public createCampaign(payload: CreateCampaignPayload): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/campaigns`, payload);
  }

  public updateCampaign(id: number, payload: Partial<CreateCampaignPayload>): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/campaigns/${id}`, payload);
  }

  public toggleCampaignStatus(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/campaigns/${id}/toggle-status`, {});
  }

  public deleteCampaign(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/campaigns/${id}`);
  }

  // =================== AUDIT LOGS ===================
  public getAuditMetrics(): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/api-audit-logs/metrics`).pipe(
      tap((res) => {
        this.auditMetrics.set(res.data);
      })
    );
  }

  public getAuditLogs(filters: { partner_id?: number; status_group?: string; from_date?: string; to_date?: string; search?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams();
    if (filters.partner_id) params = params.set('partner_id', filters.partner_id);
    if (filters.status_group) params = params.set('status_group', filters.status_group);
    if (filters.from_date) params = params.set('from_date', filters.from_date);
    if (filters.to_date) params = params.set('to_date', filters.to_date);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/api-audit-logs`, { params }).pipe(
      tap({
        next: (res) => {
          this.auditLogs.set(res.data);
          this.auditLogsMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }
}
