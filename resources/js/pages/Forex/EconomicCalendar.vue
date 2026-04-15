<script setup lang="ts">
import ForexLayout from '@/layouts/ForexLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CalendarClock, Globe, Info } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, computed } from 'vue';

// ── Breadcrumbs ──────────────────────────────────────────────────────
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Forex Journal',      href: '/forex' },
    { title: 'Economic Calendar',  href: '/forex/calendar' },
];

// ── Dynamic Timezone from user's global settings ─────────────────────
const page = usePage<any>();

const userTimezone = computed<string>(
    () => page.props.auth?.user?.timezone
       ?? (page.props.userSettings as any)?.timezone
       ?? 'UTC'
);

// ── TradingView widget ───────────────────────────────────────────────
const widgetRef = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!widgetRef.value) return;

    widgetRef.value.innerHTML = '';

    const config = {
        colorTheme:       'light',          // ← White / Light mode
        isTransparent:    false,
        width:            '100%',
        height:           '900',
        locale:           'en',
        importanceFilter: '-1,0,1',            // Medium (0) + High (1) only
        countryFilter:    'us,eu,gb,au,jp', // USD, EUR, GBP, AUD, JPY
        timezone:         userTimezone.value,
    };

    // Widget inner div
    const inner = document.createElement('div');
    inner.className = 'tradingview-widget-container__widget';
    widgetRef.value.appendChild(inner);

    // Script tag with JSON config embedded
    const script = document.createElement('script');
    script.type  = 'text/javascript';
    script.src   = 'https://s3.tradingview.com/external-embedding/embed-widget-events.js';
    script.async = true;
    script.innerHTML = JSON.stringify(config);
    widgetRef.value.appendChild(script);
});

onUnmounted(() => {
    if (widgetRef.value) widgetRef.value.innerHTML = '';
});
</script>

<template>
    <Head title="Economic Calendar — Forex Journal" />

    <ForexLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-5 p-4 md:p-6">

            <!-- ── Page heading ──────────────────────────────────── -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold tracking-tight">
                        <CalendarClock class="h-5 w-5 text-emerald-500" />
                        Economic Calendar
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        Medium &amp; High impact events only — USD · EUR · GBP · AUD · JPY
                    </p>
                </div>

                <!-- Right: currency badges + active timezone -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1">
                        <Badge variant="outline" class="text-xs">USD</Badge>
                        <Badge variant="outline" class="text-xs">EUR</Badge>
                        <Badge variant="outline" class="text-xs">GBP</Badge>
                        <Badge variant="outline" class="text-xs">AUD</Badge>
                        <Badge variant="outline" class="text-xs">JPY</Badge>
                    </div>
                    <div class="flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-950/20 dark:text-emerald-400">
                        <Globe class="h-3 w-3" />
                        {{ userTimezone }}
                    </div>
                </div>
            </div>

            <!-- ── News trading alert ───────────────────────────── -->
            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50/70 px-4 py-2.5 text-xs dark:border-amber-800/40 dark:bg-amber-950/10">
                <Info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                <span class="text-amber-800 dark:text-amber-300">
                    <strong>Rule:</strong> Avoid new trades 15 min before and after red events. Trade the post-news momentum with confirmation only.
                </span>
            </div>

            <!-- ── Full-width TradingView Calendar Card ──────────── -->
            <Card class="w-full overflow-hidden">
                <CardHeader class="border-b border-border pb-3">
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-muted-foreground" />
                        <CardTitle class="text-sm font-semibold">Live Economic Events</CardTitle>
                    </div>
                    <CardDescription class="text-xs">
                        Powered by TradingView · Timezone: {{ userTimezone }}
                    </CardDescription>
                </CardHeader>

                <!-- Zero padding so widget fills edge-to-edge -->
                <CardContent class="p-0">
                    <div
                        ref="widgetRef"
                        class="tradingview-widget-container w-full"
                        style="min-height: 900px;"
                    />
                </CardContent>
            </Card>

        </div>
    </ForexLayout>
</template>
