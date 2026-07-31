export const OrganizationService = {
    async onboard(name: string) {
        return useApi('/v1/organizations/onboarding', { method: 'POST', body: { name } });
    },

    async mine() {
        return useApi('/v1/organizations/mine');
    },

    async switchActive(organizationId: string) {
        return useApi('/v1/organizations/active', { method: 'PATCH', body: { organization_id: organizationId } });
    },

    async lookupMember(cpf: string, organizationId?: string) {
        return useApi('/v1/organizations/members/lookup', { query: { cpf, organization_id: organizationId } });
    },

    async addMember(userId: string, roleId: string, organizationId?: string) {
        return useApi('/v1/organizations/members', {
            method: 'POST',
            body: { user_id: userId, role_id: roleId, organization_id: organizationId }
        });
    },

    async createMember(name: string, email: string, cpf: string, roleId: string, organizationId?: string) {
        return useApi('/v1/organizations/members/create', {
            method: 'POST',
            body: { name, email, cpf, role_id: roleId, organization_id: organizationId }
        });
    },

    async update(organizationId: string, name: string) {
        return useApi(`/v1/organizations/${organizationId}`, { method: 'PUT', body: { name } });
    },

    async listAll() {
        return useApi('/v1/admin/organizations');
    },

    async create(name: string, parentId?: string, ownerCpf?: string) {
        return useApi('/v1/admin/organizations', { method: 'POST', body: { name, parent_id: parentId, owner_cpf: ownerCpf } });
    },

    async createSub(name: string, organizationId?: string) {
        return useApi('/v1/organizations/sub', { method: 'POST', body: { name, organization_id: organizationId } });
    },

    async listMembers(organizationId: string, filters?: { page?: number; limit?: number }) {
        return useApi(`/v1/organizations/${organizationId}/members`, { query: filters });
    },

    async transferOwnership(newOwnerUserId: string, organizationId?: string) {
        return useApi('/v1/organizations/transfer-ownership', {
            method: 'POST',
            body: { new_owner_user_id: newOwnerUserId, organization_id: organizationId }
        });
    },

    async updateMemberRole(userId: string, roleId: string, organizationId?: string) {
        return useApi(`/v1/organizations/members/${userId}/role`, {
            method: 'PUT',
            body: { role_id: roleId, organization_id: organizationId }
        });
    },

    async removeMember(userId: string, organizationId?: string) {
        return useApi(`/v1/organizations/members/${userId}`, {
            method: 'DELETE',
            query: { organization_id: organizationId }
        });
    }
};
