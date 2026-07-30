export interface Organization {
    id: string;
    name: string;
    slug: string;
    parent_id: string | null;
    members_count: number;
}

export interface OrganizationMember {
    user_id: string;
    name: string;
    email: string;
    role: {
        id: string;
        name: string;
        slug: string;
    };
}

export interface OrganizationMembership {
    organization: {
        id: string;
        name: string;
        slug: string;
    };
    role: {
        name: string;
        slug: string;
    };
    is_active: boolean;
}

export interface MemberLookupResult {
    user_id: string;
    name: string;
    email: string;
}
