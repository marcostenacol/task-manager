import { ref } from 'vue';
import type { MemberLookupResult, OrganizationMembership } from '../models/organization';
import { OrganizationService } from '../services/OrganizationService';

export const useOrganizations = () => {
    const memberships = ref<OrganizationMembership[]>([]);
    const loading = ref(false);

    const fetchMine = async () => {
        loading.value = true;
        try {
            const response = await OrganizationService.mine() as any;
            memberships.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar organizations:', error);
        } finally {
            loading.value = false;
        }
    };

    const onboard = async (name: string) => {
        try {
            await OrganizationService.onboard(name);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao fundar organization:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível criar a organization.' };
        }
    };

    const switchActive = async (organizationId: string) => {
        try {
            await OrganizationService.switchActive(organizationId);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao trocar organization ativa:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível trocar de organization.' };
        }
    };

    const lookupMember = async (cpf: string): Promise<{ success: boolean; result?: MemberLookupResult | null; message?: string }> => {
        try {
            const response = await OrganizationService.lookupMember(cpf) as any;
            return { success: true, result: response.data };
        } catch (error: any) {
            console.error('Erro ao buscar usuário por CPF:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível buscar o usuário.' };
        }
    };

    const addMember = async (userId: string, roleId: string) => {
        try {
            await OrganizationService.addMember(userId, roleId);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao adicionar membro:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível adicionar o membro.' };
        }
    };

    return {
        memberships,
        loading,
        fetchMine,
        onboard,
        switchActive,
        lookupMember,
        addMember
    };
};
