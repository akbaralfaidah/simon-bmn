export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    profile?: {
        id?: number;
        nip?: string | null;
        phone?: string | null;
        unit_id?: number | null;
        unit?: { id: number; name: string };
    } | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        roles: string[];
        can: { coordinate: boolean; inspect: boolean; administer: boolean };
    };
    unreadNotifications: number;
};
