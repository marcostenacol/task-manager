import type { AdminUser, AdminUserFilters } from '../models/admin';

export const AdminService = {
    async listUsers(filters: AdminUserFilters) {
        const { $api } = useNuxtApp();
        return await $api.get('/v1/admin/users', { params: filters });
    },

    async getUser(id: string) {
        const { $api } = useNuxtApp();
        return await $api.get(`/v1/admin/users/${id}`);
    },

    async banUser(id: string, reason: string) {
        const { $api } = useNuxtApp();
        return await $api.post(`/v1/admin/users/${id}/ban`, { reason });
    },

    async activateUser(id: string) {
        const { $api } = useNuxtApp();
        return await $api.post(`/v1/admin/users/${id}/activate`);
    },

    async changeRole(id: string, role_id: string) {
        const { $api } = useNuxtApp();
        return await $api.patch(`/v1/admin/users/${id}/role`, { role_id });
    }
};
