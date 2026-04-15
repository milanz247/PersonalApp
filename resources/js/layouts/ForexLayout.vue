<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import ForexSidebar from '@/components/ForexSidebar.vue';
import ForexSidebarHeader from '@/components/ForexSidebarHeader.vue';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<any>();
const { toast } = useToast();

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) toast({ title: 'Success', description: message, variant: 'default' });
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) toast({ title: 'Error', description: message, variant: 'destructive' });
    },
    { immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <ForexSidebar />
        <AppContent variant="sidebar">
            <ForexSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="pb-20 md:pb-0">
                <slot />
            </div>
        </AppContent>
    </AppShell>

    <Toaster />

    <!-- Footer — same style as Finance Tracker footer -->
    <footer class="border-t border-zinc-200 bg-white px-4 py-3 text-center text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
        <span class="font-medium text-zinc-700 dark:text-zinc-300">Forex Journal</span>
        &nbsp;·&nbsp;
        Developed by
        <span class="font-medium text-zinc-700 dark:text-zinc-300">Milan Madusanka</span>
        &nbsp;·&nbsp;
        &copy; {{ new Date().getFullYear() }} All rights reserved.
    </footer>
</template>
