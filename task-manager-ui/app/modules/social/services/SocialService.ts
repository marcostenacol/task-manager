import type { UserProfile } from '../models/social';

export const SocialService = {
    async getProfile() {
        const { $api } = useNuxtApp();
        return await $api.get('/v1/social/profile');
    },

    async updateProfile(data: Partial<UserProfile>) {
        const { $api } = useNuxtApp();
        return await $api.put('/v1/social/profile', data);
    },

    async uploadAvatar(file: File) {
        const { $api } = useNuxtApp();
        const formData = new FormData();
        formData.append('avatar', file);
        return await $api.post('/v1/social/profile/avatar', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },

    async updateContacts(contacts: any[]) {
        const { $api } = useNuxtApp();
        return await $api.put('/v1/social/contacts', { contacts });
    }
};
