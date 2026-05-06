import { ref } from 'vue';
import type { UserProfile } from '../models/social';
import { SocialService } from '../services/SocialService';

export const useProfile = () => {
    const profile = ref<UserProfile | null>(null);
    const loading = ref(false);

    const fetchProfile = async () => {
        loading.value = true;
        try {
            const response = await SocialService.getProfile();
            profile.value = response.data;
        } catch (error) {
            console.error('Erro ao buscar perfil:', error);
        } finally {
            loading.value = false;
        }
    };

    const updateProfile = async (data: Partial<UserProfile>) => {
        try {
            const response = await SocialService.updateProfile(data);
            profile.value = response.data;
            return true;
        } catch (error) {
            console.error('Erro ao atualizar perfil:', error);
            return false;
        }
    };

    const uploadAvatar = async (file: File) => {
        try {
            const response = await SocialService.uploadAvatar(file);
            if (profile.value) {
                profile.value.avatar_path = response.data.url;
            }
            return true;
        } catch (error) {
            console.error('Erro ao fazer upload de avatar:', error);
            return false;
        }
    };

    return {
        profile,
        loading,
        fetchProfile,
        updateProfile,
        uploadAvatar
    };
};
