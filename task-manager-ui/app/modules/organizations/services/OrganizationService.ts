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

    async lookupMember(cpf: string) {
        return useApi('/v1/organizations/members/lookup', { query: { cpf } });
    },

    async addMember(userId: string, roleId: string) {
        return useApi('/v1/organizations/members', { method: 'POST', body: { user_id: userId, role_id: roleId } });
    }
};
