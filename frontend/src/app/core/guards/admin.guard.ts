import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const adminGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  const user = authService.currentUser();
  const token = authService.token();

  if (token && user && user.role === 'admin') {
    return true;
  }

  // Si no está autenticado o no es admin, redirige al login
  router.navigate(['/login']);
  return false;
};
