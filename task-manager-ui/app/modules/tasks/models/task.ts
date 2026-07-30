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
    title: string;
    description: string | null;
    visibility: 'personal' | 'organization';
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
