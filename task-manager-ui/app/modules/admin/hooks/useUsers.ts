import { ref, reactive } from 'vue';
import type { AdminUser, AdminUserFilters } from '../models/admin';
import { AdminService } from '../services/AdminService';

export const useUsers = () => {
    const users = ref<AdminUser[]>([]);
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
            const response = await AdminService.listUsers(filters);
            users.value = response.data;
            meta.value = response.meta;
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

    return {
        users,
        loading,
        meta,
        filters,
        fetchUsers,
        applyFilters,
        banUser,
        activateUser
    };
};
