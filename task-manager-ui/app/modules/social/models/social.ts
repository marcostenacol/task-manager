export interface Contact {
    id: string;
    type: string;
    value: string;
    is_primary: boolean;
}

export type UserContact = Contact;

export interface UpsertContactData {
    type: string;
    value: string;
    is_primary?: boolean;
}

export interface UserProfile {
    id: string;
    name: string;
    email: string;
    avatar_path: string | null;
    bio: string | null;
    cpf: string | null;
    role: {
        name: string;
        slug: string;
    };
    contacts: Contact[];
    created_at: string;
}

export interface UpdateProfileData {
    name: string;
    bio: string;
    cpf?: string;
}
