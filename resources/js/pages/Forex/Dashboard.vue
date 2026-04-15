<script setup lang="ts">
import ForexLayout from '@/layouts/ForexLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingUp, BookOpen, BarChart2, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Forex Journal', href: '/forex' },
    { title: 'Dashboard',     href: '/forex' },
];

const page      = usePage<any>();
const user      = computed(() => page.props.auth?.user);
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'Milan');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good Morning';
    if (h < 18) return 'Good Afternoon';
    return 'Good Evening';
});

// ── Stats placeholders ─────────────────────────────────────────────
const stats = [
    { label: 'Total Trades',   value: '—', sub: 'entries logged', icon: BookOpen,   color: 'text-muted-foreground' },
    { label: 'Win Rate',       value: '—', sub: 'no data yet',    icon: TrendingUp, color: 'text-green-500'        },
    { label: 'Best Month',     value: '—', sub: 'no data yet',    icon: Sparkles,   color: 'text-amber-500'        },
    { label: 'Avg. R:R Ratio', value: '—', sub: 'no data yet',    icon: BarChart2,  color: 'text-blue-500'         },
];
</script>

<template>
    <Head title="Forex Journal — Dashboard" />

    <ForexLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">

            <!-- ── Greeting banner ──────────────────────────────── -->
            <Card class="border-emerald-200 bg-gradient-to-br from-emerald-50 via-background to-teal-50/30 dark:border-emerald-800/40 dark:from-emerald-950/30 dark:to-teal-950/10">
                <CardContent class="flex flex-col gap-4 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                            Forex Journal · Dashboard
                        </p>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ greeting }}, {{ firstName }} 👋
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Your personal trading journey, documented one trade at a time.
                        </p>
                    </div>
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 shadow-lg">
                        <TrendingUp class="h-8 w-8 text-white" />
                    </div>
                </CardContent>
            </Card>

            <!-- ── Stat cards ─────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">{{ stat.label }}</CardTitle>
                        <component :is="stat.icon" class="h-4 w-4" :class="stat.color" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stat.value }}</div>
                        <div class="mt-0.5 text-xs text-muted-foreground">{{ stat.sub }}</div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </ForexLayout>
</template>
