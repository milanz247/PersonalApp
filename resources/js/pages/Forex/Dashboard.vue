<script setup lang="ts">
import ForexLayout from '@/layouts/ForexLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    TrendingUp,
    BookMarked,
    BarChart2,
    Target,
    Lightbulb,
    ArrowRight,
    Plus,
    BookOpen,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Forex Journal', href: '/forex' },
    { title: 'Dashboard',     href: '/forex' },
];

const page = usePage<any>();
const user = computed(() => page.props.auth?.user);
const firstName = computed(() => user.value?.name?.split(' ')[0] ?? 'Milan');

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h < 12) return 'Good Morning';
    if (h < 18) return 'Good Afternoon';
    return 'Good Evening';
});

// ── Quick access cards ─────────────────────────────────────────────
const quickLinks = [
    {
        title:       'New Journal Entry',
        description: 'Log a new trade or session note.',
        icon:        Plus,
        href:        '/forex/journal',
        iconClass:   'text-emerald-600 dark:text-emerald-400',
        iconBg:      'bg-emerald-100 dark:bg-emerald-900/30',
    },
    {
        title:       'Analytics',
        description: 'Review your performance metrics.',
        icon:        BarChart2,
        href:        '/forex/analytics',
        iconClass:   'text-blue-600 dark:text-blue-400',
        iconBg:      'bg-blue-100 dark:bg-blue-900/30',
    },
    {
        title:       'My Journal',
        description: 'Browse all previous trade entries.',
        icon:        BookMarked,
        href:        '/forex/journal',
        iconClass:   'text-violet-600 dark:text-violet-400',
        iconBg:      'bg-violet-100 dark:bg-violet-900/30',
    },
    {
        title:       'Trading Goals',
        description: 'Track and set your trading targets.',
        icon:        Target,
        href:        '/forex/settings',
        iconClass:   'text-amber-600 dark:text-amber-400',
        iconBg:      'bg-amber-100 dark:bg-amber-900/30',
    },
];

// ── Daily tips (rotates by weekday) ───────────────────────────────
const tips = [
    'Discipline is the edge. Stick to your system.',
    'Risk management first — profits follow.',
    'Journal every trade: winners AND losers.',
    'The market is always right. Adapt, not fight.',
    'Small consistent gains beat big risky swings.',
    'Never risk more than 1–2% per trade.',
    'Review your journal weekly. Growth is in the data.',
];

const dailyTip = computed(() => tips[new Date().getDay() % tips.length]);

// ── Stats placeholders ─────────────────────────────────────────────
const stats = [
    { label: 'Total Trades',   value: '—', sub: 'entries logged',    icon: BookOpen,  color: 'text-muted-foreground' },
    { label: 'Win Rate',       value: '—', sub: 'no data yet',       icon: TrendingUp, color: 'text-green-500'        },
    { label: 'Best Month',     value: '—', sub: 'no data yet',       icon: Sparkles,  color: 'text-amber-500'        },
    { label: 'Avg. R:R Ratio', value: '—', sub: 'no data yet',       icon: BarChart2, color: 'text-blue-500'         },
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

            <!-- ── Stat cards (4 — matches Finance Tracker layout) ─ -->
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

            <!-- ── Daily Tip ────────────────────────────────────── -->
            <Card class="border-amber-200 dark:border-amber-800/40">
                <CardHeader class="flex flex-row items-center gap-2 pb-2">
                    <Lightbulb class="h-5 w-5 text-amber-500" />
                    <CardTitle class="text-base text-amber-700 dark:text-amber-400">Trader's Tip of the Day</CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm italic text-muted-foreground">"{{ dailyTip }}"</p>
                </CardContent>
            </Card>

            <!-- ── Quick Access ─────────────────────────────────── -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Quick links card -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <BookMarked class="h-5 w-5 text-muted-foreground" />
                        <CardTitle class="text-base">Quick Access</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a
                            v-for="link in quickLinks"
                            :key="link.title"
                            :href="link.href"
                            class="group flex items-center justify-between rounded-lg border border-border bg-background p-3 transition-all hover:border-emerald-300 hover:bg-emerald-50/50 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/20"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg" :class="link.iconBg">
                                    <component :is="link.icon" class="h-4 w-4" :class="link.iconClass" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium leading-none">{{ link.title }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ link.description }}</p>
                                </div>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground/40 transition-transform group-hover:translate-x-0.5 group-hover:text-muted-foreground" />
                        </a>
                    </CardContent>
                </Card>

                <!-- Empty state / getting started -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <Sparkles class="h-5 w-5 text-muted-foreground" />
                        <CardTitle class="text-base">Getting Started</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                                <TrendingUp class="h-7 w-7 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <h3 class="font-semibold">Your Dashboard is Ready</h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Start journaling your trades. Your stats,<br />charts and analytics will appear here.
                            </p>
                            <a
                                href="/forex/journal"
                                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700"
                            >
                                <Plus class="h-4 w-4" />
                                Log Your First Trade
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </ForexLayout>
</template>
