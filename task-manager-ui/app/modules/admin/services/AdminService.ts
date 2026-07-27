import type { AdminUserFilters } from '../models/admin';

export const AdminService = {
    async listUsers(filters: AdminUserFilters) {
        return useApi('/v1/admin/users', { query: filters });
    },

    async getUser(id: string) {
        return useApi(`/v1/admin/users/${id}`);
    },

    async banUser(id: string, reason: string) {
        return useApi(`/v1/admin/users/${id}/ban`, { method: 'POST', body: { reason } });
    },

    async activateUser(id: string) {
        return useApi(`/v1/admin/users/${id}/activate`, { method: 'POST' });
    },

    async changeRole(id: string, role_id: string) {
        return useApi(`/v1/admin/users/${id}/role`, { method: 'PATCH', body: { role_id } });
    }
};
