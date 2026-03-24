<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { Target, ChevronLeft, ChevronRight, Pencil } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { useFormatMoney } from '@/composables/useFormatMoney';

// ─── Types ────────────────────────────────────────────────────────────────────

interface BudgetItem {
    category_id: number;
    category_name: string;
    category_color: string | null;
    budget_amount: number;
    spent_amount: number;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    budgetData: BudgetItem[];
    currentMonth: string;
}>();

// ─── Shared ───────────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Budgets', href: '/budgets' },
];

const { formatMoney } = useFormatMoney();

// ─── Month navigation ─────────────────────────────────────────────────────────

const displayMonth = computed(() => {
    const [year, month] = props.currentMonth.split('-');
    const date = new Date(Number(year), Number(month) - 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
});

function navigateMonth(direction: -1 | 1) {
    const [year, month] = props.currentMonth.split('-').map(Number);
    const date = new Date(year, month - 1 + direction);
    const newMonth = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    router.get(route('budgets.index'), { month: newMonth }, { preserveState: true });
}

// ─── Budget stats ─────────────────────────────────────────────────────────────

const budgeted = computed(() => props.budgetData.filter(b => b.budget_amount > 0));
const totalBudget = computed(() => budgeted.value.reduce((s, b) => s + b.budget_amount, 0));
const totalSpent = computed(() => budgeted.value.reduce((s, b) => s + b.spent_amount, 0));
const overBudgetCount = computed(() => budgeted.value.filter(b => b.spent_amount > b.budget_amount).length);

// ─── Set budget dialog ────────────────────────────────────────────────────────

const dialogOpen = ref(false);
const editingItem = ref<BudgetItem | null>(null);

const budgetForm = useForm({
    category_id: 0,
    amount: '',
    month_year: props.currentMonth,
});

function openSetBudget(item: BudgetItem) {
    editingItem.value = item;
    budgetForm.category_id = item.category_id;
    budgetForm.amount = item.budget_amount > 0 ? String(item.budget_amount) : '';
    budgetForm.month_year = props.currentMonth;
    dialogOpen.value = true;
}

function submitBudget() {
    budgetForm.post(route('budgets.store'), {
        onSuccess: () => {
            dialogOpen.value = false;
            budgetForm.reset();
            editingItem.value = null;
        },
    });
}

watch(dialogOpen, (val) => {
    if (!val) {
        budgetForm.reset();
        editingItem.value = null;
    }
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getPercentage(item: BudgetItem): number {
    if (item.budget_amount <= 0) return 0;
    return Math.round((item.spent_amount / item.budget_amount) * 100);
}

function getVariant(item: BudgetItem): 'success' | 'warning' | 'danger' | 'default' {
    const pct = getPercentage(item);
    if (pct > 100) return 'danger';
    if (pct >= 80) return 'warning';
    return 'success';
}

function getStatusText(item: BudgetItem): string {
    const pct = getPercentage(item);
    if (pct > 100) return 'Over budget!';
    if (pct >= 80) return 'Approaching limit';
    return 'On track';
}

function getStatusColor(item: BudgetItem): string {
    const pct = getPercentage(item);
    if (pct > 100) return 'text-red-600 dark:text-red-400';
    if (pct >= 80) return 'text-amber-600 dark:text-amber-400';
    return 'text-green-600 dark:text-green-400';
}
</script>

<template>
    <Head title="Budgets" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Budgets</h1>
                    <p class="text-sm text-muted-foreground">Set monthly spending limits per category and track your progress.</p>
                </div>

                <!-- Month Picker -->
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" @click="navigateMonth(-1)">
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <span class="min-w-[140px] text-center text-sm font-medium">{{ displayMonth }}</span>
                    <Button variant="outline" size="icon" @click="navigateMonth(1)">
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div v-if="budgeted.length > 0" class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Budgeted</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatMoney(totalBudget) }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Spent</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="totalSpent > totalBudget ? 'text-red-600 dark:text-red-400' : ''">
                            {{ formatMoney(totalSpent) }}
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Over Budget</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="overBudgetCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                            {{ overBudgetCount }} {{ overBudgetCount === 1 ? 'category' : 'categories' }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Budget List -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Category Budgets</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="budgetData.length === 0" class="flex h-40 items-center justify-center text-muted-foreground">
                        <div class="text-center">
                            <Target class="mx-auto mb-2 h-10 w-10 opacity-40" />
                            <p>No expense categories found</p>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="item in budgetData"
                            :key="item.category_id"
                            class="rounded-lg border p-4 transition-colors hover:bg-muted/30"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: item.category_color ?? '#6b7280' }"
                                    />
                                    <span class="text-sm font-medium">{{ item.category_name }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span v-if="item.budget_amount > 0" class="text-xs" :class="getStatusColor(item)">
                                        {{ getStatusText(item) }}
                                    </span>
                                    <Button variant="ghost" size="sm" class="h-8 px-2" @click="openSetBudget(item)">
                                        <Pencil class="mr-1 h-3 w-3" />
                                        {{ item.budget_amount > 0 ? 'Edit' : 'Set Budget' }}
                                    </Button>
                                </div>
                            </div>

                            <template v-if="item.budget_amount > 0">
                                <div class="mt-3">
                                    <Progress
                                        :model-value="Math.min(item.spent_amount, item.budget_amount)"
                                        :max="item.budget_amount"
                                        :variant="getVariant(item)"
                                    />
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>{{ formatMoney(item.spent_amount) }} spent</span>
                                    <span>{{ formatMoney(item.budget_amount) }} budget</span>
                                </div>
                                <div v-if="getPercentage(item) > 100" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">
                                    {{ formatMoney(item.spent_amount - item.budget_amount) }} over budget ({{ getPercentage(item) }}%)
                                </div>
                            </template>

                            <template v-else>
                                <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                                    <span>{{ formatMoney(item.spent_amount) }} spent this month</span>
                                    <span class="italic">No budget set</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Set Budget Dialog -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{ editingItem?.budget_amount ? 'Edit' : 'Set' }} Budget — {{ editingItem?.category_name }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitBudget" class="grid gap-4">
                    <div class="grid gap-1.5">
                        <Label for="budget-amount">Monthly Budget Amount</Label>
                        <Input
                            id="budget-amount"
                            v-model="budgetForm.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            required
                        />
                        <p v-if="budgetForm.errors.amount" class="text-xs text-destructive">{{ budgetForm.errors.amount }}</p>
                    </div>

                    <div v-if="editingItem && editingItem.spent_amount > 0" class="rounded-md bg-muted/50 px-3 py-2 text-sm">
                        Already spent <strong>{{ formatMoney(editingItem.spent_amount) }}</strong> this month in {{ editingItem.category_name }}.
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="budgetForm.processing">
                            {{ budgetForm.processing ? 'Saving…' : 'Save Budget' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
