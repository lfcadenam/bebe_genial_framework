import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface AdminBabyProfile {
  id: number;
  full_name: string;
  birth_date?: string | null;
  school_grade?: string | null;
  school_name?: string | null;
}

export interface AdminEnrollment {
  id: number;
  course_id: number;
  course_title: string;
  campaign_id?: number | null;
  campaign_title?: string | null;
  partner_name?: string | null;
  enrolled_at: string;
  expires_at: string;
  access_type: 'FULL' | 'FREEMIUM';
  book_limit?: number | null;
  origin_channel?: string | null;
  is_valid: boolean;
}

export interface AdminUser {
  id: number;
  name?: string;
  first_name: string;
  last_name?: string;
  full_name: string;
  email: string;
  phone?: string | null;
  role: 'admin' | 'parent' | 'supervisor';
  status: 'active' | 'inactive' | 'suspended';
  must_change_password: boolean;
  created_at: string;
  baby_profile?: AdminBabyProfile | null;
  total_enrollments: number;
  active_enrollments: number;
  enrollments?: AdminEnrollment[];
}

export interface AdminCourse {
  id: number;
  title: string;
  description?: string;
  hours?: number;
  price?: string | number;
  is_visible: boolean;
  is_active: boolean;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface UserFilters {
  search?: string;
  role?: string;
  status?: string;
  page?: number;
  per_page?: number;
}

export interface UpdateUserPayload {
  first_name: string;
  last_name?: string;
  email: string;
  phone?: string;
  role: string;
  status: string;
  must_change_password?: boolean;
  baby_full_name?: string;
  baby_birth_date?: string;
  baby_school_grade?: string;
  baby_school_name?: string;
}

export interface CreateEnrollmentPayload {
  course_id: number;
  duration_days?: number;
  expires_at?: string;
  access_type: 'FULL' | 'FREEMIUM';
  book_limit?: number;
}

@Injectable({
  providedIn: 'root',
})
export class AdminUsersService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api/v1/admin';

  // Signals para estado reactivo
  public users = signal<AdminUser[]>([]);
  public meta = signal<PaginationMeta>({ current_page: 1, last_page: 1, per_page: 15, total: 0 });
  public courses = signal<AdminCourse[]>([]);
  public isLoading = signal<boolean>(false);
  public selectedUser = signal<AdminUser | null>(null);

  /**
   * Obtiene la lista paginada de usuarios con filtros
   */
  getUsers(filters: UserFilters = {}): Observable<{ success: boolean; data: AdminUser[]; meta: PaginationMeta }> {
    this.isLoading.set(true);

    let params = new HttpParams();
    if (filters.search) params = params.set('search', filters.search);
    if (filters.role) params = params.set('role', filters.role);
    if (filters.status) params = params.set('status', filters.status);
    if (filters.page) params = params.set('page', filters.page.toString());
    if (filters.per_page) params = params.set('per_page', filters.per_page.toString());

    return this.http.get<{ success: boolean; data: AdminUser[]; meta: PaginationMeta }>(`${this.apiUrl}/users`, { params }).pipe(
      tap({
        next: (res) => {
          this.users.set(res.data);
          this.meta.set(res.meta);
          this.isLoading.set(false);
        },
        error: () => this.isLoading.set(false),
      })
    );
  }

  /**
   * Obtiene el detalle completo de un usuario con sus matrículas
   */
  getUserById(id: number): Observable<{ success: boolean; data: AdminUser }> {
    return this.http.get<{ success: boolean; data: AdminUser }>(`${this.apiUrl}/users/${id}`).pipe(
      tap((res) => this.selectedUser.set(res.data))
    );
  }

  /**
   * Actualiza los datos del usuario y perfil de menor
   */
  updateUser(id: number, payload: UpdateUserPayload): Observable<{ success: boolean; message: string; data: any }> {
    return this.http.put<{ success: boolean; message: string; data: any }>(`${this.apiUrl}/users/${id}`, payload);
  }

  /**
   * Resetea la contraseña del usuario
   */
  resetPassword(id: number, password = '123', mustChange = true): Observable<{ success: boolean; message: string }> {
    return this.http.post<{ success: boolean; message: string }>(`${this.apiUrl}/users/${id}/reset-password`, {
      password,
      must_change_password: mustChange,
    });
  }

  /**
   * Asigna o renueva una matrícula a un curso
   */
  storeEnrollment(userId: number, payload: CreateEnrollmentPayload): Observable<{ success: boolean; message: string; data: any }> {
    return this.http.post<{ success: boolean; message: string; data: any }>(`${this.apiUrl}/users/${userId}/enrollments`, payload);
  }

  /**
   * Cancela o elimina una matrícula
   */
  deleteEnrollment(enrollmentId: number): Observable<{ success: boolean; message: string }> {
    return this.http.delete<{ success: boolean; message: string }>(`${this.apiUrl}/enrollments/${enrollmentId}`);
  }

  /**
   * Obtiene la lista de cursos disponibles en el catálogo
   */
  getCourses(): Observable<{ success: boolean; data: AdminCourse[] }> {
    return this.http.get<{ success: boolean; data: AdminCourse[] }>(`${this.apiUrl}/courses`).pipe(
      tap((res) => this.courses.set(res.data))
    );
  }
}
