import { ref } from 'vue';
import type { Setting } from '../models/admin';
import { AdminService } from '../services/AdminService';

export const useSettings = () => {
    const settings = ref<Setting[]>([]);
    const loading = ref(false);

    const fetchSettings = async () => {
        loading.value = true;
        try {
            const response = await AdminService.listSettings() as any;
            settings.value = response.data || [];
        } catch (error) {
            console.error('Erro ao buscar configurações:', error);
        } finally {
            loading.value = false;
        }
    };

    const updateSetting = async (id: number, value: string) => {
        try {
            const response = await AdminService.updateSetting(id, value) as any;
            const index = settings.value.findIndex((setting) => setting.id === id);
            if (index !== -1) {
                settings.value[index] = response.data;
            }
            return { success: true };
        } catch (error: any) {
            console.error('Erro ao atualizar configuração:', error);
            return { success: false, message: error?.data?.message || 'Erro ao atualizar configuração.' };
        }
    };

    return {
        settings,
        loading,
        fetchSettings,
        updateSetting
    };
};
