import type { Task, TaskFilters, TaskResponse } from '../models/task';

const prefix = '/v1/tasks';

export const TaskService = {
    async list(filters: TaskFilters = {}): Promise<TaskResponse> {
        return useApi(prefix, { query: filters });
    },

    async show(id: string): Promise<Task> {
        return useApi(`${prefix}/${id}`);
    },

    async create(data: Partial<Task>): Promise<Task> {
        return useApi(prefix, { method: 'POST', body: data });
    },

    async update(id: string, data: Partial<Task>): Promise<Task> {
        return useApi(`${prefix}/${id}`, { method: 'PUT', body: data });
    },

    async updateStatus(id: string, statusId: string): Promise<Task> {
        return useApi(`${prefix}/${id}/status`, { method: 'PATCH', body: { status_id: statusId } });
    },

    async delete(id: string): Promise<void> {
        return useApi(`${prefix}/${id}`, { method: 'DELETE' });
    },

    // Auxiliares para carregar opções
    async getStatuses(): Promise<any[]> {
        return useApi('/v1/task-statuses');
    },

    async getPriorities(): Promise<any[]> {
        return useApi('/v1/task-priorities');
    }
};
