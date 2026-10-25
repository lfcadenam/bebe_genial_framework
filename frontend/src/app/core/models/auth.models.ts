export interface BabyProfile {
  id: number;
  full_name: string;
  birth_date: string;
  avatar?: string;
  school_grade?: string;
  school_name?: string;
}

export interface User {
  id: number;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string;
  phone?: string;
  role: 'parent' | 'admin' | 'partner' | string;
  status: 'active' | 'inactive' | 'suspended';
  must_change_password: boolean;
  baby_profile?: BabyProfile | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
  device_name?: string;
  remember?: boolean;
}

export interface AuthSuccessData {
  token: string;
  token_type: string;
  user: User;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  meta?: {
    timestamp: string;
    version: string;
  };
}

export interface ApiErrorResponse {
  success: boolean;
  message?: string;
  code?: string;
  errors?: Record<string, string[] | string>;
}
