import type { Task, TaskFilters, TaskResponse } from '../models/task';

const prefix = '/v1/tasks';

export const TaskService = {
    async list(filters: TaskFilters = {}): Promise<TaskResponse> {
        return useApi(prefix, { query: filters });
    },

    async show(id: string): Promise<Task> {
        const response = await useApi<{ data: Task }>(`${prefix}/${id}`);
        return response.data;
    },

    async create(data: Partial<Task>): Promise<Task> {
        const response = await useApi<{ data: Task }>(prefix, { method: 'POST', body: data });
        return response.data;
    },

    async update(id: string, data: Partial<Task>): Promise<Task> {
        const response = await useApi<{ data: Task }>(`${prefix}/${id}`, { method: 'PUT', body: data });
        return response.data;
    },

    async updateStatus(id: string, statusId: string): Promise<Task> {
        const response = await useApi<{ data: Task }>(`${prefix}/${id}/status`, { method: 'PATCH', body: { status_id: statusId } });
        return response.data;
    },

    async assign(id: string, userId: string): Promise<Task> {
        const response = await useApi<{ data: Task }>(`${prefix}/${id}/assign`, { method: 'PATCH', body: { user_id: userId } });
        return response.data;
    },

    async delete(id: string): Promise<void> {
        return useApi(`${prefix}/${id}`, { method: 'DELETE' });
    },

    // Auxiliares para carregar opções
    async getStatuses(): Promise<any[]> {
        const response = await useApi<{ data: any[] }>('/v1/task-statuses');
        return response.data;
    },

    async getPriorities(): Promise<any[]> {
        const response = await useApi<{ data: any[] }>('/v1/task-priorities');
        return response.data;
    }
};
