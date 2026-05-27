import type { UserProfile } from '../models/social';

export const SocialService = {
    async getProfile() {
        return useApi('/v1/social/profile');
    },

    async updateProfile(data: Partial<UserProfile>) {
        return useApi('/v1/social/profile', { method: 'PUT', body: data });
    },

    async uploadAvatar(file: File) {
        const formData = new FormData();
        formData.append('avatar', file);
        return useApi('/v1/social/profile/avatar', {
            method: 'POST',
            body: formData
        });
    },

    async updateContacts(contacts: any[]) {
        return useApi('/v1/social/contacts', { method: 'PUT', body: { contacts } });
    }
};
