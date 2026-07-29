import { ref, reactive } from 'vue';
import type { AdminUser, AdminUserFilters, Role } from '../models/admin';
import { AdminService } from '../services/AdminService';

export const useUsers = () => {
    const users = ref<AdminUser[]>([]);
    const roles = ref<Role[]>([]);
    const loading = ref(false);
    const meta = ref<any>(null);

    const filters = reactive<AdminUserFilters>({
        search: '',
        role_id: '',
        status_id: '',
        page: 1,
        limit: 15
    });

    const fetchUsers = async () => {
        loading.value = true;
        try {
            const response = await AdminService.listUsers(filters) as any;
            if (response.success && response.data) {
                users.value = response.data.data || [];
                meta.value = response.data;
            }
        } catch (error) {
            console.error('Erro ao buscar usuários:', error);
        } finally {
            loading.value = false;
        }
    };

    const applyFilters = () => {
        filters.page = 1;
        fetchUsers();
    };

    const banUser = async (id: string, reason: string) => {
        try {
            await AdminService.banUser(id, reason);
            await fetchUsers();
            return true;
        } catch (error) {
            console.error('Erro ao banir usuário:', error);
            return false;
        }
    };

    const activateUser = async (id: string) => {
        try {
            await AdminService.activateUser(id);
            await fetchUsers();
            return true;
        } catch (error) {
            console.error('Erro ao ativar usuário:', error);
            return false;
        }
    };

    const deleteUser = async (id: string) => {
        try {
            await AdminService.deleteUser(id);
            await fetchUsers();
            return true;
        } catch (error: any) {
            console.error('Erro ao excluir usuário:', error);
            window.alert(error?.data?.message || 'Não foi possível excluir o usuário.');
            return false;
        }
    };

    const resetPassword = async (id: string, password: string) => {
        try {
            await AdminService.resetUserPassword(id, password);
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao redefinir senha:', error);
            return { success: false, message: error?.data?.message || 'Não foi possível redefinir a senha.' };
        }
    };

    const fetchRoles = async () => {
        try {
            const response = await AdminService.listRoles() as any;
            roles.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar roles:', error);
        }
    };

    return {
        users,
        roles,
        loading,
        meta,
        filters,
        fetchUsers,
        fetchRoles,
        applyFilters,
        banUser,
        activateUser,
        deleteUser,
        resetPassword
    };
};
