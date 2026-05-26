import type { Task, TaskFilters, TaskResponse } from '../models/task';

export class TaskService {
    private static prefix = '/tasks';

    static async list(filters: TaskFilters = {}): Promise<TaskResponse> {
        const { $api } = useNuxtApp();
        const response = await $api.get(this.prefix, { params: filters });
        return response.data;
    }

    static async show(id: string): Promise<Task> {
        const { $api } = useNuxtApp();
        const response = await $api.get(`${this.prefix}/${id}`);
        return response.data;
    }

    static async create(data: Partial<Task>): Promise<Task> {
        const { $api } = useNuxtApp();
        const response = await $api.post(this.prefix, data);
        return response.data;
    }

    static async update(id: string, data: Partial<Task>): Promise<Task> {
        const { $api } = useNuxtApp();
        const response = await $api.put(`${this.prefix}/${id}`, data);
        return response.data;
    }

    static async updateStatus(id: string, statusId: string): Promise<Task> {
        const { $api } = useNuxtApp();
        const response = await $api.patch(`${this.prefix}/${id}/status`, { status_id: statusId });
        return response.data;
    }

    static async delete(id: string): Promise<void> {
        const { $api } = useNuxtApp();
        await $api.delete(`${this.prefix}/${id}`);
    }

    // Auxiliares para carregar opções
    static async getStatuses(): Promise<any[]> {
        const { $api } = useNuxtApp();
        const response = await $api.get('/task-statuses');
        return response.data;
    }

    static async getPriorities(): Promise<any[]> {
        const { $api } = useNuxtApp();
        const response = await $api.get('/task-priorities');
        return response.data;
    }
}
