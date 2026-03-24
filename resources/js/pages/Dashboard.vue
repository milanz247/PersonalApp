<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useFormatMoney } from '@/composables/useFormatMoney';
import {
    Wallet,
    TrendingUp,
    TrendingDown,
    PiggyBank,
    ArrowUpRight,
    ArrowDownLeft,
    ArrowLeftRight,
    CalendarClock,
    BarChart3,
    PieChart,
    Receipt,
    Handshake,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

interface CategoryBreakdownItem {
    name: string;
    color: string | null;
    total: number;
}

interface RecentTransaction {
    id: number;
    type: 'income' | 'expense' | 'transfer';
    amount: number;
    date: string;
    note: string | null;
    category: { name: string; color: string | null } | null;
    from_account: string | null;
    to_account: string | null;
}

interface PendingDebt {
    id: number;
    person_name: string;
    type: 'borrowed' | 'lent';
    amount: number;
    remaining_amount: number;
    due_date: string | null;
    status: string;
}

const props = defineProps<{
    totalBalance: number;
    monthlyIncome: number;
    monthlyExpense: number;
    categoryBreakdown: CategoryBreakdownItem[];
    trendDates: string[];
    trendIncome: number[];
    trendExpense: number[];
    recentTransactions: RecentTransaction[];
    pendingDebts: PendingDebt[];
    totalBorrowed: number;
    totalLent: number;
}>();

const { formatMoney, currencySymbol } = useFormatMoney();

const ready = ref(false);
onMounted(() => {
    setTimeout(() => (ready.value = true), 150);
});

const savings = computed(() => props.monthlyIncome - props.monthlyExpense);

// ── Bar chart options (Income vs Expense – last 30 days) ───────────
const barOptions = computed(() => ({
    chart: {
        type: 'bar' as const,
        toolbar: { show: false },
        fontFamily: 'inherit',
        background: 'transparent',
    },
    colors: ['#22c55e', '#ef4444'],
    plotOptions: {
        bar: { borderRadius: 4, columnWidth: '60%' },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.trendDates.map((d) => {
            const date = new Date(d);
            return `${date.getDate()}/${date.getMonth() + 1}`;
        }),
        labels: {
            style: { fontSize: '10px' },
            rotate: -45,
            rotateAlways: props.trendDates.length > 15,
        },
        tickAmount: 10,
    },
    yaxis: {
        labels: {
            formatter: (v: number) => `${currencySymbol()} ${v.toLocaleString()}`,
        },
    },
    tooltip: {
        y: {
            formatter: (v: number) => formatMoney(v),
        },
    },
    legend: { position: 'top' as const },
    grid: { borderColor: 'rgba(128,128,128,0.15)' },
    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' as const : 'light' as const },
}));

const barSeries = computed(() => [
    { name: 'Income', data: props.trendIncome },
    { name: 'Expense', data: props.trendExpense },
]);

const hasBarData = computed(() =>
    props.trendIncome.some((v) => v > 0) || props.trendExpense.some((v) => v > 0),
);

// ── Donut chart options (Expense by Category) ──────────────────────
const donutOptions = computed(() => ({
    chart: {
        type: 'donut' as const,
        background: 'transparent',
        fontFamily: 'inherit',
    },
    labels: props.categoryBreakdown.map((c) => c.name),
    colors: props.categoryBreakdown.map((c) => c.color ?? '#6b7280'),
    dataLabels: {
        enabled: true,
        formatter: (_val: number, opts: { seriesIndex: number; w: { globals: { series: number[] } } }) => {
            const total = opts.w.globals.series.reduce((a: number, b: number) => a + b, 0);
            const pct = total ? ((opts.w.globals.series[opts.seriesIndex] / total) * 100).toFixed(1) : '0';
            return `${pct}%`;
        },
    },
    plotOptions: {
        pie: {
            donut: {
                size: '55%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        formatter: (w: { globals: { seriesTotals: number[] } }) =>
                            formatMoney(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0)),
                    },
                },
            },
        },
    },
    legend: { position: 'bottom' as const },
    theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' as const : 'light' as const },
}));

const donutSeries = computed(() => props.categoryBreakdown.map((c) => Number(c.total)));
const hasDonutData = computed(() => props.categoryBreakdown.length > 0);

// ── Helpers ────────────────────────────────────────────────────────
function txIcon(type: string) {
    return type === 'income' ? ArrowDownLeft : type === 'expense' ? ArrowUpRight : ArrowLeftRight;
}

function txColor(type: string) {
    return type === 'income' ? 'text-green-500' : type === 'expense' ? 'text-red-500' : 'text-blue-500';
}

function isOverdue(date: string | null) {
    if (!date) return false;
    return new Date(date) < new Date();
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- ── Stats Cards ──────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Balance -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Balance</CardTitle>
                        <Wallet class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="ready" class="text-2xl font-bold">{{ formatMoney(totalBalance) }}</div>
                        <div v-else class="h-8 w-32 animate-pulse rounded bg-muted" />
                    </CardContent>
                </Card>

                <!-- Monthly Income -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Monthly Income</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="ready" class="text-2xl font-bold text-green-600 dark:text-green-400">
                            +{{ formatMoney(monthlyIncome) }}
                        </div>
                        <div v-else class="h-8 w-32 animate-pulse rounded bg-muted" />
                    </CardContent>
                </Card>

                <!-- Monthly Expenses -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Monthly Expenses</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="ready" class="text-2xl font-bold text-red-600 dark:text-red-400">
                            -{{ formatMoney(monthlyExpense) }}
                        </div>
                        <div v-else class="h-8 w-32 animate-pulse rounded bg-muted" />
                    </CardContent>
                </Card>

                <!-- Savings -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Savings</CardTitle>
                        <PiggyBank class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="ready"
                            class="text-2xl font-bold"
                            :class="savings >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                        >
                            {{ savings >= 0 ? '+' : '' }}{{ formatMoney(savings) }}
                        </div>
                        <div v-else class="h-8 w-32 animate-pulse rounded bg-muted" />
                    </CardContent>
                </Card>
            </div>

            <!-- ── Charts Row ───────────────────────────────────── -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Bar Chart (Income vs Expense, 30 days) -->
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center gap-2">
                        <BarChart3 class="h-5 w-5 text-muted-foreground" />
                        <CardTitle class="text-base">Income vs Expenses (Last 30 Days)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <template v-if="ready">
                            <VueApexCharts
                                v-if="hasBarData"
                                type="bar"
                                height="320"
                                :options="barOptions"
                                :series="barSeries"
                            />
                            <div v-else class="flex h-[320px] items-center justify-center text-muted-foreground">
                                <div class="text-center">
                                    <BarChart3 class="mx-auto mb-2 h-10 w-10 opacity-40" />
                                    <p>No transaction data yet</p>
                                </div>
                            </div>
                        </template>
                        <div v-else class="h-[320px] animate-pulse rounded bg-muted" />
                    </CardContent>
                </Card>

                <!-- Donut Chart (Expense by Category) -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <PieChart class="h-5 w-5 text-muted-foreground" />
                        <CardTitle class="text-base">Expenses by Category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <template v-if="ready">
                            <VueApexCharts
                                v-if="hasDonutData"
                                type="donut"
                                height="320"
                                :options="donutOptions"
                                :series="donutSeries"
                            />
                            <div v-else class="flex h-[320px] items-center justify-center text-muted-foreground">
                                <div class="text-center">
                                    <PieChart class="mx-auto mb-2 h-10 w-10 opacity-40" />
                                    <p>No expense data yet</p>
                                </div>
                            </div>
                        </template>
                        <div v-else class="h-[320px] animate-pulse rounded bg-muted" />
                    </CardContent>
                </Card>
            </div>

            <!-- ── Bottom Row: Transactions + Debts ─────────────── -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Transactions -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <Receipt class="h-5 w-5 text-muted-foreground" />
                        <CardTitle class="text-base">Recent Transactions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <template v-if="ready">
                            <ul v-if="recentTransactions.length" class="divide-y divide-border">
                                <li
                                    v-for="tx in recentTransactions"
                                    :key="tx.id"
                                    class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-full"
                                            :class="
                                                tx.type === 'income'
                                                    ? 'bg-green-100 dark:bg-green-900/30'
                                                    : tx.type === 'expense'
                                                      ? 'bg-red-100 dark:bg-red-900/30'
                                                      : 'bg-blue-100 dark:bg-blue-900/30'
                                            "
                                        >
                                            <component :is="txIcon(tx.type)" class="h-4 w-4" :class="txColor(tx.type)" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium leading-none">
                                                {{ tx.category?.name ?? tx.note ?? tx.type }}
                                            </p>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                {{ tx.from_account ?? '' }}{{ tx.to_account ? ` → ${tx.to_account}` : '' }}
                                                · {{ tx.date }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold" :class="txColor(tx.type)">
                                        {{ tx.type === 'income' ? '+' : tx.type === 'expense' ? '-' : '' }}{{ formatMoney(tx.amount) }}
                                    </span>
                                </li>
                            </ul>
                            <div v-else class="flex h-40 items-center justify-center text-muted-foreground">
                                <div class="text-center">
                                    <Receipt class="mx-auto mb-2 h-10 w-10 opacity-40" />
                                    <p>No transactions yet</p>
                                </div>
                            </div>
                        </template>
                        <div v-else class="space-y-4">
                            <div v-for="i in 5" :key="i" class="flex items-center gap-3">
                                <div class="h-9 w-9 animate-pulse rounded-full bg-muted" />
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 w-24 animate-pulse rounded bg-muted" />
                                    <div class="h-3 w-40 animate-pulse rounded bg-muted" />
                                </div>
                                <div class="h-4 w-16 animate-pulse rounded bg-muted" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pending Debts -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <Handshake class="h-5 w-5 text-muted-foreground" />
                        <CardTitle class="text-base">Pending Debts</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <template v-if="ready">
                            <!-- Debt summary -->
                            <div v-if="totalBorrowed > 0 || totalLent > 0" class="mb-4 flex gap-4">
                                <div class="flex-1 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                                    <p class="text-xs text-muted-foreground">You Owe</p>
                                    <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatMoney(totalBorrowed) }}</p>
                                </div>
                                <div class="flex-1 rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                    <p class="text-xs text-muted-foreground">Owed to You</p>
                                    <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatMoney(totalLent) }}</p>
                                </div>
                            </div>

                            <ul v-if="pendingDebts.length" class="divide-y divide-border">
                                <li
                                    v-for="debt in pendingDebts"
                                    :key="debt.id"
                                    class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                                >
                                    <div class="flex items-center gap-3">
                                        <Badge
                                            :variant="debt.type === 'borrowed' ? 'destructive' : 'default'"
                                            class="w-16 justify-center text-xs"
                                        >
                                            {{ debt.type === 'borrowed' ? 'Owe' : 'Lent' }}
                                        </Badge>
                                        <div>
                                            <p class="text-sm font-medium leading-none">{{ debt.person_name }}</p>
                                            <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                                <CalendarClock class="h-3 w-3" />
                                                <template v-if="debt.due_date">
                                                    <span :class="isOverdue(debt.due_date) ? 'text-red-500 font-medium' : ''">
                                                        {{ debt.due_date }}
                                                        <span v-if="isOverdue(debt.due_date)"> (Overdue)</span>
                                                    </span>
                                                </template>
                                                <span v-else>No due date</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold">
                                        {{ formatMoney(debt.remaining_amount) }}
                                    </span>
                                </li>
                            </ul>
                            <div v-else class="flex h-40 items-center justify-center text-muted-foreground">
                                <div class="text-center">
                                    <Handshake class="mx-auto mb-2 h-10 w-10 opacity-40" />
                                    <p>No pending debts</p>
                                </div>
                            </div>
                        </template>
                        <div v-else class="space-y-4">
                            <div class="flex gap-4">
                                <div class="h-16 flex-1 animate-pulse rounded-lg bg-muted" />
                                <div class="h-16 flex-1 animate-pulse rounded-lg bg-muted" />
                            </div>
                            <div v-for="i in 3" :key="i" class="flex items-center gap-3">
                                <div class="h-6 w-16 animate-pulse rounded bg-muted" />
                                <div class="flex-1 space-y-2">
                                    <div class="h-4 w-24 animate-pulse rounded bg-muted" />
                                    <div class="h-3 w-32 animate-pulse rounded bg-muted" />
                                </div>
                                <div class="h-4 w-16 animate-pulse rounded bg-muted" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
