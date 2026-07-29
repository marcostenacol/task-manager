import { ref } from 'vue';
import type { UserProfile, UpsertContactData } from '../models/social';
import { SocialService } from '../services/SocialService';

export const useProfile = () => {
    const profile = ref<UserProfile | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const fetchProfile = async () => {
        loading.value = true;
        error.value = null;
        try {
            const response = await SocialService.getProfile();
            profile.value = response.data;
        } catch (err) {
            error.value = 'Erro ao buscar perfil';
            console.error('Erro ao buscar perfil:', err);
        } finally {
            loading.value = false;
        }
    };

    const updateProfile = async (data: Partial<UserProfile>) => {
        try {
            const response = await SocialService.updateProfile(data);
            profile.value = response.data;
            return true;
        } catch (err) {
            console.error('Erro ao atualizar perfil:', err);
            return false;
        }
    };

    const changePassword = async (data: { current_password: string; new_password: string; new_password_confirmation: string }) => {
        try {
            await SocialService.changePassword(data);
            return { success: true };
        } catch (err: any) {
            console.error('Erro ao trocar senha:', err);
            return { success: false, message: err?.data?.message || 'Erro ao trocar senha.' };
        }
    };

    const uploadAvatar = async (file: File) => {
        try {
            const response = await SocialService.uploadAvatar(file);
            if (profile.value) {
                profile.value.avatar_path = response.data.avatar_url;
            }
            return true;
        } catch (err) {
            console.error('Erro ao fazer upload de avatar:', err);
            return false;
        }
    };

    const addContact = async (data: UpsertContactData) => {
        try {
            const response = await SocialService.addContact(data);
            if (profile.value) {
                profile.value.contacts = [...profile.value.contacts, response.data];
            }
            return true;
        } catch (err) {
            console.error('Erro ao adicionar contato:', err);
            return false;
        }
    };

    const removeContact = async (id: string) => {
        try {
            await SocialService.removeContact(id);
            if (profile.value) {
                profile.value.contacts = profile.value.contacts.filter((contact) => contact.id !== id);
            }
            return true;
        } catch (err) {
            console.error('Erro ao remover contato:', err);
            return false;
        }
    };

    return {
        profile,
        loading,
        error,
        fetchProfile,
        updateProfile,
        changePassword,
        uploadAvatar,
        addContact,
        removeContact
    };
};
