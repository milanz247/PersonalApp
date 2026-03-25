<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AccountOption, type BreadcrumbItem, type CategoryOption, type SharedData } from '@/types';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import {
    Repeat, Plus, Trash2, CalendarDays, CirclePlay,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useFormatMoney } from '@/composables/useFormatMoney';

// ─── Types ────────────────────────────────────────────────────────────────────

interface RecurringItem {
    id: number;
    account_id: number;
    category_id: number;
    amount: string;
    description: string | null;
    type: 'income' | 'expense';
    frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
    start_date: string;
    last_executed_at: string | null;
    next_date: string;
    status: 'active' | 'paused';
    account: { id: number; name: string } | null;
    category: { id: number; name: string; color: string | null } | null;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const { recurringTransactions } = defineProps<{
    recurringTransactions: RecurringItem[];
}>();

// ─── Shared ───────────────────────────────────────────────────────────────────

const page = usePage<SharedData>();
const allAccounts = computed<AccountOption[]>(() => page.props.userAccounts);
const expenseCategories = computed<CategoryOption[]>(() => page.props.expenseCategories);
const incomeCategories = computed<CategoryOption[]>(() => page.props.incomeCategories);

const { formatMoney } = useFormatMoney();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recurring Transactions', href: '/recurring' },
];

// ─── Create form ──────────────────────────────────────────────────────────────

const createOpen = ref(false);

const createForm = useForm({
    type: 'expense' as 'income' | 'expense',
    account_id: '',
    category_id: '',
    amount: '',
    description: '',
    frequency: 'monthly' as 'daily' | 'weekly' | 'monthly' | 'yearly',
    start_date: new Date().toISOString().split('T')[0],
});

const categories = computed(() =>
    createForm.type === 'expense' ? expenseCategories.value : incomeCategories.value,
);

function submitCreate() {
    createForm.post(route('recurring.store'), {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

watch(createOpen, (val) => {
    if (!val) createForm.reset();
});

// ─── Actions ──────────────────────────────────────────────────────────────────

function toggleStatus(item: RecurringItem) {
    router.post(route('recurring.toggle', { recurringTransaction: item.id }), {}, {
        preserveScroll: true,
    });
}

function runNow(item: RecurringItem) {
    if (!confirm(`Execute this ${item.type} of ${formatMoney(Number(item.amount))} now?`)) return;
    router.post(route('recurring.run', { recurringTransaction: item.id }), {}, {
        preserveScroll: true,
    });
}

function deleteRecurring(item: RecurringItem) {
    if (!confirm('Delete this recurring transaction? This cannot be undone.')) return;
    router.delete(route('recurring.destroy', { recurringTransaction: item.id }), {
        preserveScroll: true,
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

const frequencyLabels: Record<string, string> = {
    daily: 'Daily',
    weekly: 'Weekly',
    monthly: 'Monthly',
    yearly: 'Yearly',
};

const frequencyColors: Record<string, string> = {
    daily: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    weekly: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    monthly: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    yearly: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
};

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Recurring Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Recurring Transactions</h1>
                    <p class="text-sm text-muted-foreground">Manage your auto-billing, subscriptions, and recurring income.</p>
                </div>

                <Dialog v-model:open="createOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            New Recurring
                        </Button>
                    </DialogTrigger>

                    <DialogContent class="max-w-[95vw] sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>New Recurring Transaction</DialogTitle>
                        </DialogHeader>

                        <form @submit.prevent="submitCreate" class="grid gap-4">
                            <!-- Type -->
                            <div class="grid gap-1.5">
                                <Label>Type</Label>
                                <div class="flex gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        :variant="createForm.type === 'expense' ? 'default' : 'outline'"
                                        @click="createForm.type = 'expense'; createForm.category_id = ''"
                                    >
                                        Expense
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        :variant="createForm.type === 'income' ? 'default' : 'outline'"
                                        @click="createForm.type = 'income'; createForm.category_id = ''"
                                    >
                                        Income
                                    </Button>
                                </div>
                            </div>

                            <!-- Account -->
                            <div class="grid gap-1.5">
                                <Label for="rc-account">Account</Label>
                                <select
                                    id="rc-account"
                                    v-model="createForm.account_id"
                                    class="border-input bg-background ring-offset-background focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                    required
                                >
                                    <option value="" disabled>Select account</option>
                                    <option v-for="acc in allAccounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }}
                                    </option>
                                </select>
                                <p v-if="createForm.errors.account_id" class="text-xs text-destructive">{{ createForm.errors.account_id }}</p>
                            </div>

                            <!-- Category -->
                            <div class="grid gap-1.5">
                                <Label for="rc-category">Category</Label>
                                <select
                                    id="rc-category"
                                    v-model="createForm.category_id"
                                    class="border-input bg-background ring-offset-background focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                    required
                                >
                                    <option value="" disabled>Select category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <p v-if="createForm.errors.category_id" class="text-xs text-destructive">{{ createForm.errors.category_id }}</p>
                            </div>

                            <!-- Amount + Frequency -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label for="rc-amount">Amount</Label>
                                    <Input id="rc-amount" v-model="createForm.amount" type="number" min="0.01" step="0.01" placeholder="0.00" required />
                                    <p v-if="createForm.errors.amount" class="text-xs text-destructive">{{ createForm.errors.amount }}</p>
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="rc-frequency">Frequency</Label>
                                    <select
                                        id="rc-frequency"
                                        v-model="createForm.frequency"
                                        class="border-input bg-background ring-offset-background focus:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                    >
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Start Date -->
                            <div class="grid gap-1.5">
                                <Label for="rc-start">Start Date</Label>
                                <Input id="rc-start" v-model="createForm.start_date" type="date" required />
                                <p v-if="createForm.errors.start_date" class="text-xs text-destructive">{{ createForm.errors.start_date }}</p>
                            </div>

                            <!-- Description -->
                            <div class="grid gap-1.5">
                                <Label for="rc-desc">Description (optional)</Label>
                                <Input id="rc-desc" v-model="createForm.description" placeholder="e.g. Netflix subscription" />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
                                <Button type="submit" :disabled="createForm.processing">
                                    {{ createForm.processing ? 'Creating…' : 'Create' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- List -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">All Recurring Transactions</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="recurringTransactions.length === 0" class="flex h-40 items-center justify-center text-muted-foreground">
                        <div class="text-center">
                            <Repeat class="mx-auto mb-2 h-10 w-10 opacity-40" />
                            <p>No recurring transactions yet</p>
                            <p class="mt-1 text-xs">Create one to automate your regular income and expenses.</p>
                        </div>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="item in recurringTransactions"
                            :key="item.id"
                            class="flex flex-col gap-3 rounded-lg border p-4 transition-colors sm:flex-row sm:items-center sm:justify-between"
                            :class="item.status === 'paused' ? 'opacity-60' : ''"
                        >
                            <!-- Left: Info -->
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                                    :class="item.type === 'income' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'"
                                >
                                    <Repeat class="h-4 w-4" :class="item.type === 'income' ? 'text-green-500' : 'text-red-500'" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium">
                                            {{ item.description || item.category?.name || 'Untitled' }}
                                        </span>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="frequencyColors[item.frequency]"
                                        >
                                            {{ frequencyLabels[item.frequency] }}
                                        </span>
                                        <Badge v-if="item.status === 'paused'" variant="secondary" class="text-xs">Paused</Badge>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-muted-foreground">
                                        <span>{{ item.account?.name ?? '—' }}</span>
                                        <span v-if="item.category" class="flex items-center gap-1">
                                            <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: item.category.color ?? '#6b7280' }" />
                                            {{ item.category.name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <CalendarDays class="h-3 w-3" />
                                            Next: {{ formatDate(item.next_date) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Amount + Actions -->
                            <div class="flex items-center gap-3 sm:shrink-0">
                                <span class="text-sm font-semibold" :class="item.type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ item.type === 'income' ? '+' : '-' }}{{ formatMoney(Number(item.amount)) }}
                                </span>

                                <!-- Toggle -->
                                <Switch
                                    :model-value="item.status === 'active'"
                                    @update:model-value="toggleStatus(item)"
                                />

                                <!-- Run Now -->
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    title="Run Now"
                                    :disabled="item.status === 'paused'"
                                    @click="runNow(item)"
                                >
                                    <CirclePlay class="h-4 w-4" />
                                </Button>

                                <!-- Delete -->
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 text-destructive hover:text-destructive"
                                    title="Delete"
                                    @click="deleteRecurring(item)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
