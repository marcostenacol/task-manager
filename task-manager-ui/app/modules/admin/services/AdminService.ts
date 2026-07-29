import type { AdminUserFilters, CreateUserData, UpdateUserData } from '../models/admin';

export const AdminService = {
    async listUsers(filters: AdminUserFilters) {
        return useApi('/v1/admin/users', { query: filters });
    },

    async listRoles() {
        return useApi('/v1/admin/roles');
    },

    async createUser(data: CreateUserData) {
        return useApi('/v1/admin/users', { method: 'POST', body: data });
    },

    async updateUser(id: string, data: UpdateUserData) {
        return useApi(`/v1/admin/users/${id}`, { method: 'PUT', body: data });
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
    },

    async listAuditLogs(filters: Record<string, unknown> = {}) {
        return useApi('/v1/admin/audit-logs', { query: filters });
    },

    async getRole(id: string) {
        return useApi(`/v1/admin/roles/${id}`);
    },

    async createRole(name: string) {
        return useApi('/v1/admin/roles', { method: 'POST', body: { name } });
    },

    async syncRolePermissions(id: string, permission_ids: string[]) {
        return useApi(`/v1/admin/roles/${id}/permissions`, { method: 'PUT', body: { permission_ids } });
    },

    async listPermissions() {
        return useApi('/v1/admin/permissions');
    }
};
