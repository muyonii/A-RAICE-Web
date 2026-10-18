export interface User {
    id: number;
    name: string;
    email: string;
    role: 'farmer' | 'admin' | 'lgu_staff';
    email_verified_at?: string;
}

export interface Alert {
    id: number;
    message: string;
    severity: 'low' | 'medium' | 'high';
    disease: string | null;
    municipality: string;
    created_at: string;
    read_at: string | null;
}

export interface PageProps {
    auth: {
        user: User;
    };
    unread_alerts_count: number;
}

export interface DiseaseSummary {
    id: number;
    name: string;
    description: string;
    image_path: string | null;
    treatments_count: number;
}
