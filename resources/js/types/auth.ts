export type User = {
    id: number;
    name: string;
    email: string;
    role: 'farmer' | 'admin' | 'lgu_staff';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Alert = {
    id: number;
    message: string;
    severity: 'low' | 'medium' | 'high';
    disease: string | null;
    municipality: string;
    created_at: string;
    read_at: string | null;
};

export type Auth = {
    user: User;
};

export type PageProps = {
    auth: Auth;
    unread_alerts_count: number;
};
