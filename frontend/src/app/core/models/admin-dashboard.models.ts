export interface KpiMetric {
  value: number | string;
  active?: number;
  growth: string;
}

export interface AdminKpis {
  total_students: KpiMetric;
  active_partners: KpiMetric;
  active_campaigns: KpiMetric;
  active_enrollments: KpiMetric;
  stories_read: KpiMetric;
  estimated_revenue: KpiMetric;
}

export interface RecentUser {
  id: number;
  full_name: string;
  email: string;
  phone?: string;
  status: string;
  baby_name: string;
  birth_date?: string;
  created_at: string;
}

export interface AnalyticsSeries {
  labels: string[];
  enrollments: number[];
  stories: number[];
}

export interface AdminDashboardData {
  kpis: AdminKpis;
  recent_users: RecentUser[];
  analytics_series: AnalyticsSeries;
}
