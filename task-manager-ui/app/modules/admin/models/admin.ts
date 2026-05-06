export interface Role {
    id: string;
    name: string;
    slug: string;
}

export interface UserStatus {
    id: string;
    name: string;
    slug: string;
}

export interface AdminUser {
    id: string;
    name: string;
    email: string;
    avatar_path: string | null;
    role: {
        name: string;
        slug: string;
    };
    status: {
        name: string;
        slug: string;
    };
    created_at: string;
}

export interface AdminUserFilters {
    search?: string;
    role_id?: string;
    status_id?: string;
    page: number;
    limit: number;
}
