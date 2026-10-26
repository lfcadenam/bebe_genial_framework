import { Component, OnInit, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminAcademicService, CourseCatalogItem } from '../../../core/services/admin-academic.service';
import { CourseDrawerComponent } from './components/course-drawer/course-drawer.component';

@Component({
  selector: 'app-cursos',
  standalone: true,
  imports: [CommonModule, FormsModule, CourseDrawerComponent],
  templateUrl: './cursos.component.html',
})
export class CursosComponent implements OnInit {
  public academicService = inject(AdminAcademicService);

  public searchTerm = signal<string>('');
  public statusFilter = signal<string>('');
  public currentPage = signal<number>(1);
  public pageSize = signal<number>(8); // 8 cursos = exactamente 2 filas de 4 columnas
  public viewMode = signal<'grid' | 'table'>('grid');

  public isDrawerOpen = signal<boolean>(false);
  public selectedCourse = signal<CourseCatalogItem | null>(null);

  public flashMessage = signal<string | null>(null);

  /**
   * Resuelve la URL de imagen oficial o legacy para cada curso
   */
  public getCourseImage(c: CourseCatalogItem): string {
    if (c.cover_image) {
      if (c.cover_image.startsWith('http') || c.cover_image.startsWith('/')) {
        return c.cover_image;
      }
      return `/images/cursos/${c.cover_image}`;
    }
    // Mapeo legacy por ID conocido si no tiene cover_image
    if (c.id === 1) return '/images/cursos/curso_metodo_bebe_poliglota.jpg';
    if (c.id === 3) return '/images/cursos/CuRSoOj59wmHKPvmUqltpW8LZ.png';
    if (c.id === 5) return '/images/cursos/CuRSo6CZoZHCqgVcVWtJrr2mD.png';
    if (c.id === 6) return '/images/cursos/CuRSozlRJPWqvuKgWv78cIhS3.png';
    if (c.id === 22) return '/images/cursos/CuRSoJe0rNerVCFd6jmZH0GRk.png';
    if (c.id === 23) return '/images/cursos/CuRSo6XO4tDfWsxxNa0SwUPjJ.png';
    if (c.id === 24) return '/images/cursos/CuRSoQTE3VyfCRRnqo64oXL6A.png';
    return '/images/cursos/bebe-genial.png';
  }

  public onImgError(event: Event): void {
    const target = event.target as HTMLImageElement;
    if (target) {
      target.src = '/images/cursos/bebe-genial.png';
    }
  }

  ngOnInit(): void {
    this.loadCourses();
  }

  public loadCourses(): void {
    this.academicService
      .getCourseCatalog({
        search: this.searchTerm(),
        status: this.statusFilter() || undefined,
        page: this.currentPage(),
        per_page: this.pageSize(),
      })
      .subscribe();
  }

  public onSearchChange(term: string): void {
    this.searchTerm.set(term);
    this.currentPage.set(1);
    this.loadCourses();
  }

  public setStatusFilter(status: string): void {
    this.statusFilter.set(status);
    this.currentPage.set(1);
    this.loadCourses();
  }

  public onPageSizeChange(size: number): void {
    this.pageSize.set(Number(size));
    this.currentPage.set(1);
    this.loadCourses();
  }

  public onPageChange(page: number): void {
    const lastPage = this.academicService.coursesMeta()?.last_page || 1;
    if (page >= 1 && page <= lastPage) {
      this.currentPage.set(page);
      this.loadCourses();
      // Desplazamiento suave al tope del catálogo
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  public getPagesArray(): number[] {
    const totalPages = this.academicService.coursesMeta()?.last_page || 1;
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }

  public getPaginationRange(): { start: number; end: number; total: number } {
    const meta = this.academicService.coursesMeta();
    const total = meta?.total || 0;
    if (total === 0) return { start: 0, end: 0, total: 0 };
    const start = (this.currentPage() - 1) * this.pageSize() + 1;
    const end = Math.min(this.currentPage() * this.pageSize(), total);
    return { start, end, total };
  }

  public openNewCourse(): void {
    this.selectedCourse.set(null);
    this.isDrawerOpen.set(true);
  }

  public openEditCourse(course: CourseCatalogItem): void {
    this.selectedCourse.set(course);
    this.isDrawerOpen.set(true);
  }

  public closeDrawer(): void {
    this.isDrawerOpen.set(false);
  }

  public onCourseSaved(): void {
    this.loadCourses();
    this.showFlash('Curso guardado correctamente.');
  }

  public onToggleActive(course: CourseCatalogItem): void {
    this.academicService.toggleCourseActive(course.id).subscribe({
      next: () => {
        this.loadCourses();
        this.showFlash(`Estado del curso "${course.title}" modificado.`);
      },
      error: () => this.showFlash('Error al modificar estado del curso.'),
    });
  }

  public onToggleVisibility(course: CourseCatalogItem): void {
    this.academicService.toggleCourseVisibility(course.id).subscribe({
      next: () => {
        this.loadCourses();
        this.showFlash(`Visibilidad de "${course.title}" modificada.`);
      },
      error: () => this.showFlash('Error al modificar visibilidad.'),
    });
  }

  public onDeleteCourse(course: CourseCatalogItem): void {
    if (!confirm(`¿Estás seguro de eliminar el curso "${course.title}"?`)) {
      return;
    }

    this.academicService.deleteCourse(course.id).subscribe({
      next: () => {
        this.loadCourses();
        this.showFlash(`Curso "${course.title}" eliminado.`);
      },
      error: (err: any) => {
        this.showFlash(err.error?.message || 'Error al eliminar el curso.');
      },
    });
  }

  private showFlash(msg: string): void {
    this.flashMessage.set(msg);
    setTimeout(() => this.flashMessage.set(null), 4000);
  }
}
