import { ref } from 'vue';
import type { Permission, Role, RoleDetail } from '../models/admin';
import { AdminService } from '../services/AdminService';

export const useRoles = () => {
    const roles = ref<Role[]>([]);
    const permissions = ref<Permission[]>([]);
    const loading = ref(false);

    const fetchRoles = async () => {
        loading.value = true;
        try {
            const response = await AdminService.listRoles() as any;
            roles.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar roles:', error);
        } finally {
            loading.value = false;
        }
    };

    const fetchPermissions = async () => {
        try {
            const response = await AdminService.listPermissions() as any;
            permissions.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar permissões:', error);
        }
    };

    const getRole = async (id: string): Promise<RoleDetail | null> => {
        try {
            const response = await AdminService.getRole(id) as any;
            return response.data;
        } catch (error) {
            console.error('Erro ao buscar role:', error);
            return null;
        }
    };

    const createRole = async (name: string) => {
        await AdminService.createRole(name);
        await fetchRoles();
    };

    const syncRolePermissions = async (id: string, permissionIds: string[]) => {
        await AdminService.syncRolePermissions(id, permissionIds);
        await fetchRoles();
    };

    const deleteRole = async (id: string) => {
        try {
            await AdminService.deleteRole(id);
            await fetchRoles();
            return true;
        } catch (error: any) {
            console.error('Erro ao excluir role:', error);
            window.alert(error?.data?.message || 'Não foi possível excluir a role.');
            return false;
        }
    };

    return {
        roles,
        permissions,
        loading,
        fetchRoles,
        fetchPermissions,
        getRole,
        createRole,
        syncRolePermissions,
        deleteRole
    };
};
