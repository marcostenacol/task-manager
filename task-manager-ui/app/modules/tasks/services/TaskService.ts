import type { Task, TaskFilters, TaskResponse } from '../models/task';

export class TaskService {
    private static prefix = '/tasks';

    static async list(filters: TaskFilters = {}): Promise<TaskResponse> {
        const response = await $api(this.prefix, {
            method: 'GET',
            query: filters
        });
        return response.data;
    }

    static async show(id: string): Promise<Task> {
        const response = await $api(`${this.prefix}/${id}`, {
            method: 'GET'
        });
        return response.data;
    }

    static async create(data: Partial<Task>): Promise<Task> {
        const response = await $api(this.prefix, {
            method: 'POST',
            body: data
        });
        return response.data;
    }

    static async update(id: string, data: Partial<Task>): Promise<Task> {
        const response = await $api(`${this.prefix}/${id}`, {
            method: 'PUT',
            body: data
        });
        return response.data;
    }

    static async updateStatus(id: string, statusId: string): Promise<Task> {
        const response = await $api(`${this.prefix}/${id}/status`, {
            method: 'PATCH',
            body: { status_id: statusId }
        });
        return response.data;
    }

    static async delete(id: string): Promise<void> {
        await $api(`${this.prefix}/${id}`, {
            method: 'DELETE'
        });
    }

    // Auxiliares para carregar opções
    static async getStatuses(): Promise<any[]> {
        const response = await $api('/task-statuses'); // Supondo que exista ou vou criar
        return response.data;
    }

    static async getPriorities(): Promise<any[]> {
        const response = await $api('/task-priorities'); // Supondo que exista ou vou criar
        return response.data;
    }
}
