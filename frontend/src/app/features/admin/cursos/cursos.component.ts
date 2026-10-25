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

  public isDrawerOpen = signal<boolean>(false);
  public selectedCourse = signal<CourseCatalogItem | null>(null);

  public flashMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.loadCourses();
  }

  public loadCourses(): void {
    this.academicService
      .getCourseCatalog({
        search: this.searchTerm(),
        status: this.statusFilter() || undefined,
        page: this.currentPage(),
        per_page: 15,
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

  public onPageChange(page: number): void {
    if (page >= 1 && page <= this.academicService.coursesMeta().last_page) {
      this.currentPage.set(page);
      this.loadCourses();
    }
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
