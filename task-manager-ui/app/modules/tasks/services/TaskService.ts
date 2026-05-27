import type { Task, TaskFilters, TaskResponse } from '../models/task';

export class TaskService {
    private static prefix = '/v1/tasks';

    static async list(filters: TaskFilters = {}): Promise<TaskResponse> {
        return useApi(this.prefix, { query: filters });
    }

    static async show(id: string): Promise<Task> {
        return useApi(`${this.prefix}/${id}`);
    }

    static async create(data: Partial<Task>): Promise<Task> {
        return useApi(this.prefix, { method: 'POST', body: data });
    }

    static async update(id: string, data: Partial<Task>): Promise<Task> {
        return useApi(`${this.prefix}/${id}`, { method: 'PUT', body: data });
    }

    static async updateStatus(id: string, statusId: string): Promise<Task> {
        return useApi(`${this.prefix}/${id}/status`, { method: 'PATCH', body: { status_id: statusId } });
    }

    static async delete(id: string): Promise<void> {
        return useApi(`${this.prefix}/${id}`, { method: 'DELETE' });
    }

    // Auxiliares para carregar opções
    static async getStatuses(): Promise<any[]> {
        return useApi('/v1/task-statuses');
    }

    static async getPriorities(): Promise<any[]> {
        return useApi('/v1/task-priorities');
    }
}
