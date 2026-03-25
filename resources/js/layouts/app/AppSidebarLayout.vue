<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItemType, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { HandCoins, LayoutGrid, ListOrdered, Settings, Tag, Wallet, Target, Repeat, FileBarChart } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const { toast } = useToast();

watch(
    () => page.props.flash.success,
    (message) => {
        if (message) {
            toast({ title: 'Success', description: message, variant: 'default' });
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash.error,
    (message) => {
        if (message) {
            toast({ title: 'Error', description: message, variant: 'destructive' });
        }
    },
    { immediate: true },
);

// ─── Mobile sheet menu ────────────────────────────────────────────────────────
const mobileMenuOpen = ref(false);

const menuItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Accounts', href: '/accounts', icon: Wallet },
    { title: 'Transactions', href: '/transactions', icon: ListOrdered },
    { title: 'Categories', href: '/categories', icon: Tag },
    { title: 'Budgets', href: '/budgets', icon: Target },
    { title: 'Recurring', href: '/recurring', icon: Repeat },
    { title: 'Debts & Loans', href: '/debts', icon: HandCoins },
    { title: 'Reports', href: '/reports', icon: FileBarChart },
    { title: 'Settings', href: '/settings', icon: Settings },
];
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <!-- Add bottom padding on mobile so content doesn't hide behind bottom nav -->
            <div class="pb-20 md:pb-0">
                <slot />
            </div>
        </AppContent>
    </AppShell>

    <!-- Mobile Bottom Navigation -->
    <MobileBottomNav @open-menu="mobileMenuOpen = true" />

    <!-- Mobile Sheet Menu (Drawer) -->
    <Sheet v-model:open="mobileMenuOpen">
        <SheetContent side="left" class="w-72 p-0">
            <SheetHeader class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <SheetTitle class="text-base">Personal HQ</SheetTitle>
            </SheetHeader>
            <nav class="flex flex-col gap-1 p-3">
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex min-h-[48px] items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors active:bg-zinc-100 dark:active:bg-zinc-800"
                    :class="page.url.startsWith(item.href)
                        ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white'
                        : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-900'"
                    @click="mobileMenuOpen = false"
                >
                    <component :is="item.icon" class="h-5 w-5" />
                    <span>{{ item.title }}</span>
                </Link>
            </nav>
        </SheetContent>
    </Sheet>

    <Toaster />
</template>
