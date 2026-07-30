export interface User {
  id: string;
  name: string;
  email: string;
  avatar_path: string | null;
  bio: string | null;
  status: {
    name: string;
    slug: string;
  };
  role: {
    name: string;
    slug: string;
  };
  organization: {
    id: string;
    name: string;
    slug: string;
  } | null;
  permissions: string[];
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
