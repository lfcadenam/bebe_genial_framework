import { Component, EventEmitter, Input, OnInit, Output, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminApiCampanasService, ApiPartner, Campaign, CreateCampaignPayload, CuentoCategory, CuentoItem } from '../../../../../core/services/admin-api-campanas.service';
import { AdminCourse, AdminUsersService } from '../../../../../core/services/admin-users.service';

@Component({
  selector: 'app-campaign-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './campaign-modal.component.html',
})
export class CampaignModalComponent implements OnInit {
  private campanasService = inject(AdminApiCampanasService);
  private usersService = inject(AdminUsersService);

  @Input() isOpen = false;
  @Input() partners: ApiPartner[] = [];
  @Input() set campaign(val: Campaign | null) {
    this._campaign = val;
    if (val) {
      this.initForm(val);
    } else {
      this.resetForm();
    }
  }
  get campaign(): Campaign | null {
    return this._campaign;
  }

  @Output() close = new EventEmitter<void>();
  @Output() campaignSaved = new EventEmitter<void>();

  private _campaign: Campaign | null = null;
  public courses = this.usersService.courses;
  public isSaving = signal<boolean>(false);
  public errorMessage = signal<string | null>(null);

  // Catálogo de cuentos interactivos
  public categories = signal<CuentoCategory[]>([]);
  public totalCatalogStories = signal<number>(365);
  public activeCategoryId = signal<number>(1);
  public selectedStoryIds = signal<Set<number>>(new Set());
  public storySearch = signal<string>('');
  public isLoadingCatalog = signal<boolean>(false);

  public form: CreateCampaignPayload = {
    code: '',
    name: '',
    partner_id: null,
    course_id: 24,
    book_limit: 365,
    duration_days: 30,
    starts_at: new Date().toISOString().substring(0, 10),
    ends_at: `${new Date().getFullYear()}-12-31`,
    is_active: true,
  };

  ngOnInit(): void {
    this.loadCatalog();
  }

  private loadCatalog(): void {
    if (this.categories().length > 0) return;
    this.isLoadingCatalog.set(true);
    this.campanasService.getCuentosCatalog().subscribe({
      next: (res) => {
        if (res.success && res.data) {
          this.categories.set(res.data);
          this.totalCatalogStories.set(res.total_stories || 365);
          if (!this.campaign && Number(this.form.course_id) === 24 && this.selectedStoryIds().size === 0) {
            this.selectAllStories(true);
          }
        }
        this.isLoadingCatalog.set(false);
      },
      error: () => this.isLoadingCatalog.set(false),
    });
  }

  private initForm(campaign: Campaign): void {
    this.form = {
      code: campaign.code,
      name: campaign.name,
      partner_id: campaign.partner_id || null,
      course_id: campaign.course_id,
      book_limit: campaign.book_limit,
      duration_days: campaign.duration_days,
      starts_at: campaign.starts_at?.substring(0, 10) || '',
      ends_at: campaign.ends_at?.substring(0, 10) || '',
      is_active: campaign.is_active,
    };
    this.errorMessage.set(null);
    this.storySearch.set('');

    if (campaign.course_id === 24) {
      this.loadCatalog();
      if (campaign.cuentos_permitidos && Array.isArray(campaign.cuentos_permitidos) && campaign.cuentos_permitidos.length > 0) {
        this.selectedStoryIds.set(new Set(campaign.cuentos_permitidos));
      } else {
        // null = acceso completo a todos los 365 cuentos
        this.selectAllStories(true);
      }
    } else {
      this.selectedStoryIds.set(new Set());
    }
  }

  private resetForm(): void {
    const defaultCourse = this.courses().find((c: AdminCourse) => c.id === 24) || this.courses()[0];
    const courseId = defaultCourse ? defaultCourse.id : 24;
    const today = new Date().toISOString().substring(0, 10);
    const endOfYear = `${new Date().getFullYear()}-12-31`;

    this.form = {
      code: '',
      name: '',
      partner_id: this.partners.length > 0 ? this.partners[0].id : null,
      course_id: courseId,
      book_limit: courseId === 24 ? 365 : 12,
      duration_days: 30,
      starts_at: today,
      ends_at: endOfYear,
      is_active: true,
    };
    this.errorMessage.set(null);
    this.storySearch.set('');

    if (courseId === 24) {
      this.loadCatalog();
      this.selectAllStories(true);
    } else {
      this.selectedStoryIds.set(new Set());
    }
  }

  public onCourseChange(courseId: any): void {
    this.form.course_id = Number(courseId);
    if (this.form.course_id === 24) {
      this.loadCatalog();
      if (this.selectedStoryIds().size === 0) {
        this.selectAllStories(true);
      }
    }
  }

  // Helpers de Selección de Cuentos
  public isStorySelected(id: number): boolean {
    return this.selectedStoryIds().has(id);
  }

  public toggleStory(id: number): void {
    const updated = new Set(this.selectedStoryIds());
    if (updated.has(id)) {
      updated.delete(id);
    } else {
      updated.add(id);
    }
    this.selectedStoryIds.set(updated);
  }

  public selectAllStories(select: boolean): void {
    if (!select) {
      this.selectedStoryIds.set(new Set());
      return;
    }

    const all = new Set<number>();
    if (this.categories().length > 0) {
      for (const cat of this.categories()) {
        for (const story of cat.cuentos) {
          all.add(story.id);
        }
      }
    } else {
      for (let i = 1; i <= 365; i++) {
        all.add(i);
      }
    }
    this.selectedStoryIds.set(all);
  }

  public setActiveCategory(catId: number): void {
    this.activeCategoryId.set(catId);
  }

  public get activeCategory(): CuentoCategory | undefined {
    return this.categories().find((c) => c.id === this.activeCategoryId()) || this.categories()[0];
  }

  public categorySelectedCount(catId: number): number {
    const cat = this.categories().find((c) => c.id === catId);
    if (!cat) return 0;
    const current = this.selectedStoryIds();
    return cat.cuentos.filter((s) => current.has(s.id)).length;
  }

  public isCategoryFullySelected(catId: number): boolean {
    const cat = this.categories().find((c) => c.id === catId);
    if (!cat || cat.cuentos.length === 0) return false;
    const current = this.selectedStoryIds();
    return cat.cuentos.every((s) => current.has(s.id));
  }

  public toggleCategory(catId: number, event?: Event): void {
    if (event) {
      event.stopPropagation();
    }
    const cat = this.categories().find((c) => c.id === catId);
    if (!cat) return;

    const updated = new Set(this.selectedStoryIds());
    const fullySelected = this.isCategoryFullySelected(catId);

    for (const story of cat.cuentos) {
      if (fullySelected) {
        updated.delete(story.id);
      } else {
        updated.add(story.id);
      }
    }
    this.selectedStoryIds.set(updated);
  }

  public markActiveCategory(select: boolean): void {
    const cat = this.activeCategory;
    if (!cat) return;
    const updated = new Set(this.selectedStoryIds());
    for (const story of cat.cuentos) {
      if (select) {
        updated.add(story.id);
      } else {
        updated.delete(story.id);
      }
    }
    this.selectedStoryIds.set(updated);
  }

  public get displayedStories(): { story: CuentoItem; categoryName: string }[] {
    const q = this.storySearch().toLowerCase().trim();
    if (q) {
      const results: { story: CuentoItem; categoryName: string }[] = [];
      for (const cat of this.categories()) {
        for (const story of cat.cuentos) {
          if (story.title.toLowerCase().includes(q)) {
            results.push({ story, categoryName: cat.name });
          }
        }
      }
      return results;
    }

    const currentCat = this.activeCategory;
    if (!currentCat) return [];
    return currentCat.cuentos.map((story) => ({ story, categoryName: currentCat.name }));
  }

  public onSubmit(): void {
    if (!this.form.code.trim() || !this.form.name.trim()) {
      this.errorMessage.set('El código y el nombre de la campaña son obligatorios.');
      return;
    }

    const isLeoCourse = Number(this.form.course_id) === 24;
    const selectedCount = this.selectedStoryIds().size;

    if (isLeoCourse && selectedCount === 0) {
      this.errorMessage.set('Debes seleccionar al menos un cuento interactivo para la campaña Leo con LEO.');
      return;
    }

    this.isSaving.set(true);
    this.errorMessage.set(null);

    const payload: CreateCampaignPayload = {
      ...this.form,
      code: this.form.code.toUpperCase().trim(),
      partner_id: this.form.partner_id ? Number(this.form.partner_id) : null,
      course_id: Number(this.form.course_id),
      book_limit: isLeoCourse ? selectedCount : Number(this.form.book_limit || 0),
      duration_days: Number(this.form.duration_days),
      cuentos_seleccionados: isLeoCourse ? Array.from(this.selectedStoryIds()) : undefined,
    };

    const action$ = this.campaign
      ? this.campanasService.updateCampaign(this.campaign.id, payload)
      : this.campanasService.createCampaign(payload);

    action$.subscribe({
      next: () => {
        this.isSaving.set(false);
        this.campaignSaved.emit();
        this.close.emit();
      },
      error: (err: any) => {
        this.isSaving.set(false);
        this.errorMessage.set(err.error?.message || 'Error al guardar la campaña.');
      },
    });
  }

  public onClose(): void {
    this.close.emit();
  }
}
