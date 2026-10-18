export * from './auth';

export type RecentScan = {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    confidence_score: string | null;
    scan_date: string;
    disease: string | null;
};

export type StaffRecentScan = RecentScan & {
    farmer_name: string | null;
};

export type DashboardStats = {
    active_outbreaks: number;
    scans_last_7_days: number;
};

export type AdminStats = {
    users_count: number;
    diseases_count: number;
};

export type ActiveOutbreak = {
    disease: string;
    severity: 'low' | 'medium' | 'high';
    started_at: string | null;
};

export type DiseaseSummary = {
    id: number;
    name: string;
    description: string;
    image_path: string | null;
    treatments_count: number;
};

export type TreatmentType = 'chemical' | 'biological' | 'cultural' | 'organic';

export interface Treatment {
    id: number;
    disease_id: number;
    title: string;
    description: string;
    type: TreatmentType;
}

export interface Disease {
    id: number;
    name: string;
    description: string;
    causes: string | null;
    symptoms: string | null;
    history: string | null;
    sources: string | null;
    prevention_tips: string | null;
    image_path: string | null;
    treatments: Treatment[];
}

export interface Scan {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    image_url: string;
    confidence_score: number | null;
    gps_lat: number | null;
    gps_long: number | null;
    scan_date: string;
    disease: Disease | null;
    farmer?: {
        id: number;
        full_name: string;
        contact_number: string | null;
    } | null;
}
