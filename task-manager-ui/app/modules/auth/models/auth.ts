export interface User {
  id: string;
  name: string;
  email: string;
  last_status_id: number;
  last_status_name: string;
  last_status_slug: string;
  role_id: number;
  role_name: string;
  role_slug: string;
  terms_accepted: boolean;
  permissions: string[];
  created_at: string;
}

export interface AccessToken {
  token: string;
  user_id: string;
  created_at: string;
}

export interface RefreshToken {
  token: string;
  created_at: string;
}

export interface AuthData {
  access_token: AccessToken;
  refresh_token: RefreshToken;
  user_data: {
    user: User;
  };
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data: AuthData;
}
