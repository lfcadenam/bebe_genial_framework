import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { AdminCourse } from './admin-users.service';

export interface CourseCatalogItem extends AdminCourse {
  status?: string;
  hours?: number;
  price?: string | number;
  is_visible: boolean;
  cover_image?: string | null;
  background_image?: string | null;
  enrollments_count?: number;
  created_at?: string;
}

export interface PadresContenidoItem {
  id: number;
  section: 'smart_cards' | 'aprendamos_juntos';
  category?: string | null;
  title: string;
  resource_type: 'video' | 'pdf' | 'article';
  url_video?: string | null;
  file_path?: string | null;
  thumbnail?: string | null;
  description?: string | null;
  order_index: number;
  allow_download: boolean;
  is_active: boolean;
  created_at: string;
}

export interface CourseTrialItem {
  id: number;
  user_id: number;
  user?: any;
  course_id: number;
  course?: AdminCourse;
  days: number;
  starts_at: string;
  expires_at: string;
  status: 'active' | 'expired' | 'revoked';
  created_at: string;
}

export interface EmailTemplateItem {
  id: number;
  slug: string;
  name: string;
  subject: string;
  body_html: string;
  available_variables?: string[] | null;
  updated_at: string;
}

export interface CertificateItem {
  id: number;
  user_id: number;
  user?: any;
  course_id: number;
  course?: AdminCourse;
  certificate_code: string;
  grade: string;
  file_path?: string | null;
  issued_at: string;
  status: 'valid' | 'revoked';
}

@Injectable({
  providedIn: 'root',
})
export class AdminAcademicService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api/v1/admin';

  // State Signals
  public courses = signal<CourseCatalogItem[]>([]);
  public coursesMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public smartCards = signal<PadresContenidoItem[]>([]);
  public smartCardsCategories = signal<string[]>([]);
  public smartCardsMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public aprendamosJuntos = signal<PadresContenidoItem[]>([]);
  public aprendamosCategories = signal<string[]>([]);
  public aprendamosMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public trials = signal<CourseTrialItem[]>([]);
  public trialsMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public omniResults = signal<any[]>([]);

  public emailTemplates = signal<EmailTemplateItem[]>([]);

  public certificates = signal<CertificateItem[]>([]);
  public certificatesMeta = signal<any>({ current_page: 1, last_page: 1, total: 0 });

  public isLoading = signal<boolean>(false);

  // =================== CURSOS CATÁLOGO ===================
  public getCourseCatalog(filters: { search?: string; status?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams();
    if (filters.search) params = params.set('search', filters.search);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/course-catalog`, { params }).pipe(
      tap({
        next: (res) => {
          this.courses.set(res.data);
          this.coursesMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public createCourse(payload: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/course-catalog`, payload);
  }

  public updateCourse(id: number, payload: any): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/course-catalog/${id}`, payload);
  }

  public toggleCourseActive(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/course-catalog/${id}/toggle-active`, {});
  }

  public toggleCourseVisibility(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/course-catalog/${id}/toggle-visibility`, {});
  }

  public deleteCourse(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/course-catalog/${id}`);
  }

  // =================== PADRES CONTENIDOS (SMART CARDS & APRENDAMOS JUNTOS) ===================
  public getPadresContenidos(section: 'smart_cards' | 'aprendamos_juntos', filters: { category?: string; search?: string; status?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams().set('section', section);
    if (filters.category) params = params.set('category', filters.category);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/padres-contenidos`, { params }).pipe(
      tap({
        next: (res) => {
          if (section === 'smart_cards') {
            this.smartCards.set(res.data);
            this.smartCardsCategories.set(res.categories || []);
            this.smartCardsMeta.set(res.meta);
          } else {
            this.aprendamosJuntos.set(res.data);
            this.aprendamosCategories.set(res.categories || []);
            this.aprendamosMeta.set(res.meta);
          }
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public createPadresContenido(payload: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/padres-contenidos`, payload);
  }

  public updatePadresContenido(id: number, payload: any): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/padres-contenidos/${id}`, payload);
  }

  public togglePadresContenidoStatus(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/padres-contenidos/${id}/toggle-status`, {});
  }

  public deletePadresContenido(id: number): Observable<any> {
    return this.http.delete<any>(`${this.apiUrl}/padres-contenidos/${id}`);
  }

  // =================== TRIALS ===================
  public getTrials(filters: { course_id?: number; status?: string; search?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams();
    if (filters.course_id) params = params.set('course_id', filters.course_id);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/trials`, { params }).pipe(
      tap({
        next: (res) => {
          this.trials.set(res.data);
          this.trialsMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public assignTrial(payload: { user_id: number; course_id: number; days: number }): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/trials`, payload);
  }

  public revokeTrial(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/trials/${id}/revoke`, {});
  }

  // =================== CONSULTAS / OMNI SEARCH ===================
  public omniSearch(query: string): Observable<any> {
    this.isLoading.set(true);
    return this.http.get<any>(`${this.apiUrl}/omni-search`, { params: { q: query } }).pipe(
      tap({
        next: (res) => {
          this.omniResults.set(res.data);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  // =================== PLANTILLAS CORREO ===================
  public getEmailTemplates(): Observable<any> {
    this.isLoading.set(true);
    return this.http.get<any>(`${this.apiUrl}/email-templates`).pipe(
      tap({
        next: (res) => {
          this.emailTemplates.set(res.data);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public updateEmailTemplate(id: number, payload: { name: string; subject: string; body_html: string }): Observable<any> {
    return this.http.put<any>(`${this.apiUrl}/email-templates/${id}`, payload);
  }

  // =================== CERTIFICADOS & DIPLOMAS ===================
  public getCertificates(filters: { course_id?: number; status?: string; search?: string; page?: number; per_page?: number } = {}): Observable<any> {
    this.isLoading.set(true);
    let params = new HttpParams();
    if (filters.course_id) params = params.set('course_id', filters.course_id);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.search) params = params.set('search', filters.search);
    if (filters.page) params = params.set('page', filters.page);
    if (filters.per_page) params = params.set('per_page', filters.per_page);

    return this.http.get<any>(`${this.apiUrl}/certificates`, { params }).pipe(
      tap({
        next: (res) => {
          this.certificates.set(res.data);
          this.certificatesMeta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  public emitCertificate(payload: { user_id: number; course_id: number; grade?: string; certificate_code?: string }): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/certificates`, payload);
  }

  public revokeCertificate(id: number): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/certificates/${id}/revoke`, {});
  }
}
