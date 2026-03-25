<script setup lang="ts">
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Wallet, ListOrdered, Menu } from 'lucide-vue-next';

defineEmits<{
    (e: 'openMenu'): void;
}>();

const page = usePage<SharedData>();

const tabs = [
    { title: 'Home', href: '/dashboard', icon: LayoutGrid },
    { title: 'Accounts', href: '/accounts', icon: Wallet },
    { title: 'Transactions', href: '/transactions', icon: ListOrdered },
];
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-50 flex h-16 items-center justify-around border-t border-zinc-200 bg-white/80 pb-safe backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950/80 md:hidden">
        <Link
            v-for="tab in tabs"
            :key="tab.href"
            :href="tab.href"
            class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 text-xs transition-colors active:scale-95"
            :class="page.url.startsWith(tab.href)
                ? 'text-zinc-900 dark:text-white font-medium'
                : 'text-zinc-500 dark:text-zinc-400'"
        >
            <component :is="tab.icon" class="h-5 w-5" />
            <span>{{ tab.title }}</span>
        </Link>
        <button
            class="flex min-h-[48px] flex-1 flex-col items-center justify-center gap-0.5 text-xs text-zinc-500 transition-colors active:scale-95 dark:text-zinc-400"
            @click="$emit('openMenu')"
        >
            <Menu class="h-5 w-5" />
            <span>Menu</span>
        </button>
    </nav>
</template>
