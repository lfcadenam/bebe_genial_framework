import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/login/login.component';
import { DashboardComponent } from './features/dashboard/dashboard.component';
import { AdminLayoutComponent } from './layouts/admin-layout/admin-layout.component';
import { AdminDashboardComponent } from './features/admin/dashboard/dashboard.component';
import { UsuariosComponent } from './features/admin/usuarios/usuarios.component';
import { ConveniosComponent } from './features/admin/convenios/convenios.component';
import { WompiComponent } from './features/admin/wompi/wompi.component';
import { CampanasComponent } from './features/admin/campanas/campanas.component';
import { CursosComponent } from './features/admin/cursos/cursos.component';
import { SmartCardsComponent } from './features/admin/smart-cards/smart-cards.component';
import { AprendamosJuntosComponent } from './features/admin/aprendamos-juntos/aprendamos-juntos.component';
import { TrialsComponent } from './features/admin/trials/trials.component';
import { ConsultasComponent } from './features/admin/consultas/consultas.component';
import { PlantillasCorreoComponent } from './features/admin/plantillas-correo/plantillas-correo.component';
import { CertificadosComponent } from './features/admin/certificados/certificados.component';
import { adminGuard } from './core/guards/admin.guard';

export const routes: Routes = [
  {
    path: 'login',
    component: LoginComponent,
    title: 'Iniciar Sesión | Bebé Genial',
  },
  {
    path: 'dashboard',
    component: DashboardComponent,
    title: 'Mi Aula | Bebé Genial',
  },
  {
    path: 'admin',
    component: AdminLayoutComponent,
    canActivate: [adminGuard],
    children: [
      {
        path: 'dashboard',
        component: AdminDashboardComponent,
        title: 'Panel Administrativo | Bebé Genial',
      },
      {
        path: 'usuarios',
        component: UsuariosComponent,
        title: 'Gestión de Usuarios & Alumnos | Bebé Genial',
      },
      {
        path: 'cursos',
        component: CursosComponent,
        title: 'Catálogo de Cursos Pedagógicos | Bebé Genial',
      },
      {
        path: 'smart-cards',
        component: SmartCardsComponent,
        title: 'Smart Cards & Fichas Didácticas | Bebé Genial',
      },
      {
        path: 'aprendamos-juntos',
        component: AprendamosJuntosComponent,
        title: 'Zona Padres & Aprendamos Juntos | Bebé Genial',
      },
      {
        path: 'trials',
        component: TrialsComponent,
        title: 'Accesos Demo & Demostraciones | Bebé Genial',
      },
      {
        path: 'certificados',
        component: CertificadosComponent,
        title: 'Diplomas & Certificados Oficiales | Bebé Genial',
      },
      {
        path: 'convenios',
        component: ConveniosComponent,
        title: 'Convenios & Distribuidores | Bebé Genial',
      },
      {
        path: 'campanas',
        component: CampanasComponent,
        title: 'API Leo con LEO B2B & Campañas | Bebé Genial',
      },
      {
        path: 'wompi',
        component: WompiComponent,
        title: 'Planes & Pagos Wompi | Bebé Genial',
      },
      {
        path: 'consultas',
        component: ConsultasComponent,
        title: 'Centro de Consultas & OmniSearch | Bebé Genial',
      },
      {
        path: 'plantillas-correo',
        component: PlantillasCorreoComponent,
        title: 'Plantillas de Correo Dinámicas | Bebé Genial',
      },
      {
        path: '',
        redirectTo: 'dashboard',
        pathMatch: 'full',
      },
      {
        path: '**',
        redirectTo: 'dashboard',
      },
    ],
  },
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full',
  },
  {
    path: '**',
    redirectTo: 'login',
  },
];
