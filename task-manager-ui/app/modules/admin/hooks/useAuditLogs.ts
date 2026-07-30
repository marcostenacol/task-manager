import { reactive, ref } from 'vue';
import type { AuditLog } from '../models/admin';
import { AdminService } from '../services/AdminService';

export interface AuditLogFilters {
    action: string;
    actor_name: string;
    organization_id: string;
    date_from: string;
    date_to: string;
}

export const useAuditLogs = () => {
    const logs = ref<AuditLog[]>([]);
    const loading = ref(false);
    const currentPage = ref(1);
    const lastPage = ref(1);
    const total = ref(0);

    const filters = reactive<AuditLogFilters>({
        action: '',
        actor_name: '',
        organization_id: '',
        date_from: '',
        date_to: ''
    });

    const fetchLogs = async (page = 1) => {
        loading.value = true;
        try {
            const response = await AdminService.listAuditLogs({ ...filters, page }) as any;
            logs.value = response.data?.data || [];
            currentPage.value = response.data?.current_page || 1;
            lastPage.value = response.data?.last_page || 1;
            total.value = response.data?.total || 0;
        } catch (error) {
            console.error('Erro ao buscar logs de auditoria:', error);
        } finally {
            loading.value = false;
        }
    };

    const applyFilters = () => fetchLogs(1);

    const nextPage = () => {
        if (currentPage.value < lastPage.value) {
            fetchLogs(currentPage.value + 1);
        }
    };

    const previousPage = () => {
        if (currentPage.value > 1) {
            fetchLogs(currentPage.value - 1);
        }
    };

    return {
        logs,
        loading,
        currentPage,
        lastPage,
        total,
        filters,
        fetchLogs,
        applyFilters,
        nextPage,
        previousPage
    };
};
