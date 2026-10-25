import { Component, inject } from '@angular/core';

import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-dashboard-placeholder',
  standalone: true,
  imports: [],
  template: `
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-lg w-full bg-white rounded-[32px] shadow-[0_20px_50px_rgba(15,23,42,0.06)] border border-slate-100 p-8 sm:p-10 text-center">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 mb-6">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Sesión Activa
        </div>

        <!-- Avatar / Iniciales -->
        <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white text-2xl font-bold shadow-md shadow-emerald-500/25 mb-4">
          {{ authService.currentUser()?.first_name?.charAt(0) || 'U' }}
        </div>

        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
          ¡Bienvenido, {{ authService.currentUser()?.first_name }}!
        </h2>
        <p class="text-sm text-slate-500 mt-1 mb-6">
          {{ authService.currentUser()?.email }}
        </p>

        <!-- Información de perfil sincronizada desde Laravel -->
        <div class="bg-slate-50 rounded-2xl p-5 mb-6 text-left border border-slate-100 space-y-2.5">
          <div class="flex justify-between text-xs">
            <span class="text-slate-400 font-medium">Rol en plataforma:</span>
            <span class="font-bold text-slate-700 capitalize">{{ authService.currentUser()?.role }}</span>
          </div>
          <div class="flex justify-between text-xs">
            <span class="text-slate-400 font-medium">Estado de cuenta:</span>
            <span class="font-bold text-emerald-600 uppercase">{{ authService.currentUser()?.status }}</span>
          </div>
          @if (authService.currentUser()?.baby_profile) {
            <div class="pt-2 border-t border-slate-200/60 flex justify-between text-xs">
              <span class="text-slate-400 font-medium">Bebé asignado:</span>
              <span class="font-bold text-slate-800">{{ authService.currentUser()?.baby_profile?.full_name }}</span>
            </div>
            <div class="flex justify-between text-xs">
              <span class="text-slate-400 font-medium">Fecha de nacimiento:</span>
              <span class="text-slate-600">{{ authService.currentUser()?.baby_profile?.birth_date }}</span>
            </div>
          }
        </div>

        <button
          (click)="onLogout()"
          class="w-full py-3.5 px-6 rounded-full font-semibold text-slate-700 text-sm bg-slate-100 hover:bg-slate-200 active:scale-[0.99] transition-all duration-200 cursor-pointer"
        >
          Cerrar sesión
        </button>
      </div>
    </div>
  `,
})
export class DashboardComponent {
  readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  onLogout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: () => {
        this.router.navigate(['/login']);
      },
    });
  }
}
