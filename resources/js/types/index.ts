import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface CategoryOption {
    id: number;
    name: string;
    color: string | null;
}

export interface AccountOption {
    id: number;
    name: string;
    type: string;
    balance: string;
}

export interface UserSettings {
    currency_symbol: string;
    currency_code: string;
    timezone: string;
    date_format: string;
    avatar_url: string | null;
}

export interface SharedData {
    [key: string]: unknown;
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: {
        success: string | null;
        error: string | null;
    };
    userSettings: UserSettings | null;
    expenseCategories: CategoryOption[];
    incomeCategories: CategoryOption[];
    userAccounts: AccountOption[];
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    currency_symbol: string;
    currency_code: string;
    timezone: string;
    date_format: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

