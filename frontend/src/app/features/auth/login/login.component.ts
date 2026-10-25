import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css',
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  readonly showPassword = signal<boolean>(false);
  readonly errorMessage = signal<string | null>(null);
  readonly showForgotModal = signal<boolean>(false);
  readonly forgotSuccessMessage = signal<string | null>(null);
  readonly forgotErrorMessage = signal<string | null>(null);
  readonly isForgotSubmitting = signal<boolean>(false);

  // Formulario principal de Login
  readonly loginForm: FormGroup = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
    remember: [true],
  });

  // Formulario de recuperación de contraseña
  readonly forgotForm: FormGroup = this.fb.group({
    forgotEmail: ['', [Validators.required, Validators.email]],
  });

  togglePasswordVisibility(): void {
    this.showPassword.update((prev) => !prev);
  }

  onSubmit(): void {
    this.errorMessage.set(null);

    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      return;
    }

    const { email, password, remember } = this.loginForm.value;

    this.authService
      .login({
        email,
        password,
        remember,
        device_name: 'Bebé Genial Web Client',
      })
      .subscribe({
        next: (response) => {
          if (response.success) {
            // Si el usuario requiere cambio obligatorio de contraseña o va al dashboard
            const user = response.data.user;
            if (user.must_change_password) {
              this.router.navigate(['/cambiar-contrasena']);
            } else if (user.role === 'admin') {
              this.router.navigate(['/admin/dashboard']);
            } else {
              this.router.navigate(['/dashboard']);
            }
          }
        },
        error: (err) => {
          const apiMessage =
            err.error?.message ||
            err.error?.errors?.email?.[0] ||
            'Error al iniciar sesión. Por favor verifica tus credenciales.';
          this.errorMessage.set(apiMessage);
        },
      });
  }

  openForgotModal(): void {
    this.forgotSuccessMessage.set(null);
    this.forgotErrorMessage.set(null);
    this.forgotForm.reset();
    this.showForgotModal.set(true);
  }

  closeForgotModal(): void {
    this.showForgotModal.set(false);
  }

  onForgotSubmit(): void {
    if (this.forgotForm.invalid) {
      this.forgotForm.markAllAsTouched();
      return;
    }

    this.isForgotSubmitting.set(true);
    this.forgotSuccessMessage.set(null);
    this.forgotErrorMessage.set(null);

    const email = this.forgotForm.value.forgotEmail;

    // Simula o llama al servicio de recuperación
    setTimeout(() => {
      this.isForgotSubmitting.set(false);
      this.forgotSuccessMessage.set(
        `Si el correo ${email} se encuentra registrado, hemos enviado las instrucciones para restablecer tu contraseña.`
      );
    }, 800);
  }
}
