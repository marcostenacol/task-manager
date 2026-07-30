import { ref } from 'vue';
import type { MemberLookupResult, Organization, OrganizationMember, OrganizationMembership } from '../models/organization';
import { OrganizationService } from '../services/OrganizationService';

export const useOrganizations = () => {
    const memberships = ref<OrganizationMembership[]>([]);
    const allOrganizations = ref<Organization[]>([]);
    const members = ref<OrganizationMember[]>([]);
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

    const fetchAllOrganizations = async () => {
        loading.value = true;
        try {
            const response = await OrganizationService.listAll() as any;
            allOrganizations.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar todas as organizations:', error);
        } finally {
            loading.value = false;
        }
    };

    const fetchMembers = async (organizationId: string) => {
        loading.value = true;
        try {
            const response = await OrganizationService.listMembers(organizationId) as any;
            members.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar membros da organization:', error);
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

    const lookupMember = async (cpf: string, organizationId?: string): Promise<{ success: boolean; result?: MemberLookupResult | null; message?: string }> => {
        try {
            const response = await OrganizationService.lookupMember(cpf, organizationId) as any;
            return { success: true, result: response.data };
        } catch (error: any) {
            console.error('Erro ao buscar usuário por CPF:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível buscar o usuário.' };
        }
    };

    const addMember = async (userId: string, roleId: string, organizationId?: string) => {
        try {
            await OrganizationService.addMember(userId, roleId, organizationId);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao adicionar membro:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível adicionar o membro.' };
        }
    };

    const updateOrganization = async (organizationId: string, name: string) => {
        try {
            await OrganizationService.update(organizationId, name);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao atualizar organization:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível atualizar a organization.' };
        }
    };

    const createOrganization = async (name: string, parentId?: string) => {
        try {
            await OrganizationService.create(name, parentId);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao criar organization:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível criar a organization.' };
        }
    };

    return {
        memberships,
        allOrganizations,
        members,
        loading,
        fetchMine,
        fetchAllOrganizations,
        fetchMembers,
        onboard,
        switchActive,
        lookupMember,
        addMember,
        updateOrganization,
        createOrganization
    };
};
