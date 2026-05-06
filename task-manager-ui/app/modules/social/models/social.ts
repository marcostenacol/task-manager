export interface Contact {
    id: string;
    type: string;
    value: string;
    is_primary: boolean;
}

export interface UserProfile {
    id: string;
    name: string;
    email: string;
    avatar_path: string | null;
    bio: string | null;
    role: {
        name: string;
        slug: string;
    };
    contacts: Contact[];
    created_at: string;
}
