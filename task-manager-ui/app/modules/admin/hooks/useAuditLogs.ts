import { ref } from 'vue';
import type { AuditLog } from '../models/admin';
import { AdminService } from '../services/AdminService';

export const useAuditLogs = () => {
    const logs = ref<AuditLog[]>([]);
    const loading = ref(false);

    const fetchLogs = async () => {
        loading.value = true;
        try {
            const response = await AdminService.listAuditLogs() as any;
            logs.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar logs de auditoria:', error);
        } finally {
            loading.value = false;
        }
    };

    return {
        logs,
        loading,
        fetchLogs
    };
};
