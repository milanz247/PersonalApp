<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

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
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
    </AppShell>
    <Toaster />
</template>
