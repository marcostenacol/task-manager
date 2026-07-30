export interface Role {
    id: string;
    name: string;
    slug: string;
    level: number;
    color: string;
    scope: 'global' | 'organization';
    permissions_count?: number;
}

export interface RoleDetail {
    id: string;
    name: string;
    slug: string;
    level: number;
    color: string;
    permission_ids: string[];
}

export interface Setting {
    id: number;
    name: string;
    value: string;
    description: string | null;
}

export interface Permission {
    id: string;
    name: string;
    description: string | null;
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
        id: string;
        name: string;
        slug: string;
        level: number;
        color: string;
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

export interface CreateUserData {
    name: string;
    email: string;
    password: string;
    role_id: string;
}

export interface UpdateUserData {
    name?: string;
    email?: string;
    role_id?: string;
}

export interface AuditLog {
    id: string;
    action: string;
    actor: {
        id: string | null;
        name: string | null;
    };
    target: {
        type: string;
        id: string;
        name: string | null;
    };
    metadata: Record<string, unknown> | [];
    created_at: string;
}
