export interface TaskStatus {
    id: string;
    name: string;
    slug: string;
}

export interface TaskPriority {
    id: string;
    name: string;
    slug: string;
    order: number;
}

export interface Task {
    id: string;
    user_id: string;
    title: string;
    description: string | null;
    visibility: 'personal' | 'organization';
    organization_id: string | null;
    due_date: string | null;
    completed_at: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    created_at: string;
    updated_at: string;
}

export interface TaskFilters {
    search?: string;
    status_id?: string;
    priority_id?: string;
    due_date?: string;
    organization_id?: string;
    completed?: boolean;
    view?: 'all';
    page?: number;
    limit?: number;
}

export interface TaskResponse {
    data: Task[];
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}
