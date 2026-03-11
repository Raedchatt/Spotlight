export type User = {
    id: number;
    username: string;
    name: string;
    email: string;
    avatar?: string;
    role: string;
    telephone?: string;
    about?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    organisateur?: {
        id: number;
        rib?: string;
        rib_popup_seen: boolean;
        [key: string]: any;
    };
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
