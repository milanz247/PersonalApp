<script setup lang="ts">
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, computed } from 'vue';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<SharedData>();

// ── User name ─────────────────────────────────────────────────────
const firstName = computed(() => {
    const name = page.props.auth?.user?.name ?? 'Milan';
    return name.split(' ')[0];
});

// ── Live clock (matches Finance Tracker pattern exactly) ───────────
function formatCurrentDate(format: string): string {
    const now  = new Date();
    const d    = String(now.getDate()).padStart(2, '0');
    const mo   = String(now.getMonth() + 1).padStart(2, '0');
    const y    = String(now.getFullYear());
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    switch (format) {
        case 'DD/MM/YYYY':   return `${d}/${mo}/${y}`;
        case 'MM/DD/YYYY':   return `${mo}/${d}/${y}`;
        case 'YYYY-MM-DD':   return `${y}-${mo}-${d}`;
        case 'DD-MM-YYYY':   return `${d}-${mo}-${y}`;
        case 'YYYY/MM/DD':   return `${y}/${mo}/${d}`;
        case 'MMM DD, YYYY': return `${months[now.getMonth()]} ${d}, ${y}`;
        default:             return `${d}/${mo}/${y}`;
    }
}

const dateFormat  = page.props.userSettings?.date_format ?? 'DD/MM/YYYY';
const currentDate = ref(formatCurrentDate(dateFormat));
const currentTime = ref(
    new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
);

let clockTimer: ReturnType<typeof setInterval>;

onMounted(() => {
    clockTimer = setInterval(() => {
        currentDate.value = formatCurrentDate(dateFormat);
        currentTime.value = new Date().toLocaleTimeString([], {
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        });
    }, 1000);
});

onUnmounted(() => clearInterval(clockTimer));
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- Left: sidebar trigger + breadcrumbs -->
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href">{{ item.title }}</BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <!-- Right: Welcome + live clock + logout -->
        <div class="flex items-center gap-4">

            <!-- Welcome text -->
            <span class="hidden text-sm font-medium text-muted-foreground lg:block">
                Welcome, <span class="font-semibold text-foreground">{{ firstName }}</span> 👋
            </span>

            <!-- Live date/time (matches Finance Tracker exactly) -->
            <div class="hidden flex-col items-end gap-0.5 md:flex">
                <span class="text-muted-foreground text-xs">{{ currentDate }}</span>
                <span class="font-mono text-sm tabular-nums leading-none">{{ currentTime }}</span>
            </div>

            <!-- Logout icon button (matches Finance Tracker) -->
            <Link
                method="post"
                :href="route('logout')"
                as="button"
                class="text-muted-foreground hover:text-foreground inline-flex h-8 w-8 items-center justify-center rounded-md transition-colors hover:bg-muted"
                title="Sign out"
            >
                <LogOut class="h-4 w-4" />
            </Link>
        </div>
    </header>
</template>
