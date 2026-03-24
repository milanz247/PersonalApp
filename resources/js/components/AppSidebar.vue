<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { HandCoins, LayoutGrid, ListOrdered, Settings, Tag, Wallet, Target, Repeat, FileBarChart } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Accounts',
        href: '/accounts',
        icon: Wallet,
    },
    {
        title: 'Transactions',
        href: '/transactions',
        icon: ListOrdered,
    },
    {
        title: 'Categories',
        href: '/categories',
        icon: Tag,
    },
    {
        title: 'Budgets',
        href: '/budgets',
        icon: Target,
    },
    {
        title: 'Recurring',
        href: '/recurring',
        icon: Repeat,
    },
    {
        title: 'Debts & Loans',
        href: '/debts',
        icon: HandCoins,
    },
    {
        title: 'Reports',
        href: '/reports',
        icon: FileBarChart,
    },
    {
        title: 'Settings',
        href: '/settings',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

    </Sidebar>
    <slot />
</template>
