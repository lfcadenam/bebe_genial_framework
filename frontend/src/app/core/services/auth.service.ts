import { Injectable, inject, signal, computed } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, tap, catchError, throwError, finalize } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  ApiResponse,
  AuthSuccessData,
  LoginCredentials,
  User
} from '../models/auth.models';

const TOKEN_KEY = 'bbp_auth_token';
const USER_KEY = 'bbp_auth_user';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  // Estados reactivos con Signals
  readonly currentUser = signal<User | null>(this.getStoredUser());
  readonly token = signal<string | null>(this.getStoredToken());
  readonly isLoading = signal<boolean>(false);
  readonly isAuthenticated = computed(() => !!this.token() && !!this.currentUser());

  /**
   * Inicia sesión contra el backend de Laravel Sanctum
   */
  login(credentials: LoginCredentials): Observable<ApiResponse<AuthSuccessData>> {
    this.isLoading.set(true);

    const payload = {
      email: credentials.email.trim().toLowerCase(),
      password: credentials.password,
      device_name: credentials.device_name || 'Bebé Genial Web Portal',
    };

    return this.http
      .post<ApiResponse<AuthSuccessData>>(`${this.apiUrl}/auth/login`, payload)
      .pipe(
        tap((response) => {
          if (response.success && response.data) {
            this.setSession(response.data.token, response.data.user, credentials.remember ?? true);
          }
        }),
        finalize(() => this.isLoading.set(false))
      );
  }

  /**
   * Cierra la sesión en el backend y limpia almacenamiento local
   */
  logout(): Observable<any> {
    const currentToken = this.token();
    const headers = currentToken
      ? new HttpHeaders().set('Authorization', `Bearer ${currentToken}`)
      : undefined;

    return this.http
      .post(`${this.apiUrl}/auth/logout`, {}, { headers })
      .pipe(
        finalize(() => {
          this.clearSession();
        })
      );
  }

  /**
   * Solicita el restablecimiento de contraseña
   */
  requestPasswordReset(email: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/forgot-password`, { email });
  }

  private setSession(token: string, user: User, remember: boolean): void {
    this.token.set(token);
    this.currentUser.set(user);

    const storage = remember ? localStorage : sessionStorage;
    storage.setItem(TOKEN_KEY, token);
    storage.setItem(USER_KEY, JSON.stringify(user));
  }

  private clearSession(): void {
    this.token.set(null);
    this.currentUser.set(null);
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    sessionStorage.removeItem(TOKEN_KEY);
    sessionStorage.removeItem(USER_KEY);
  }

  private getStoredToken(): string | null {
    return localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY);
  }

  private getStoredUser(): User | null {
    const raw = localStorage.getItem(USER_KEY) || sessionStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
      return JSON.parse(raw) as User;
    } catch {
      return null;
    }
  }
}
