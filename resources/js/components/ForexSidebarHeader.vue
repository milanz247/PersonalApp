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
import type { BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, Globe } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, computed } from 'vue';
import { useDateTime } from '@/composables/useDateTime';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<any>();

// ── User info ──────────────────────────────────────────────────────
const firstName = computed(() => {
    const name = page.props.auth?.user?.name ?? 'Milan';
    return name.split(' ')[0];
});

// ── Timezone-aware live clock ──────────────────────────────────────
const { userTimezone, formatNowDate, formatNowTime } = useDateTime();

const currentDate = ref('');
const currentTime = ref('');

let clockTimer: ReturnType<typeof setInterval>;

function updateClock() {
    currentDate.value = formatNowDate();        // respects user's date_format & timezone
    currentTime.value = formatNowTime(true);    // 12-hour clock in user's timezone
}

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);
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

        <!-- Right: Welcome + timezone clock + logout -->
        <div class="flex items-center gap-4">

            <!-- Welcome text -->
            <span class="hidden text-sm font-medium text-muted-foreground lg:block">
                Welcome, <span class="font-semibold text-foreground">{{ firstName }}</span> 👋
            </span>

            <!-- Live timezone clock -->
            <div class="hidden flex-col items-end gap-0.5 md:flex">
                <!-- Timezone label -->
                <span class="flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-emerald-500 dark:text-emerald-400">
                    <Globe class="h-2.5 w-2.5" />
                    {{ userTimezone }}
                </span>
                <!-- Date in user's format + timezone -->
                <span class="text-muted-foreground text-xs">{{ currentDate }}</span>
                <!-- Time in user's timezone -->
                <span class="font-mono text-sm tabular-nums leading-none">{{ currentTime }}</span>
            </div>

            <!-- Logout icon (matches Finance Tracker) -->
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
