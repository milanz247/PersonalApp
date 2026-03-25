<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { ArrowRight, Trash2 } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';

// ─── Types ────────────────────────────────────────────────────────────────────

interface AccountRef {
    id: number;
    name: string;
}

interface CategoryRef {
    id: number;
    name: string;
    type: string;
    is_system: boolean;
    color: string | null;
}

interface Transaction {
    id: number;
    type: 'transfer' | 'expense' | 'income';
    amount: string;
    fee: string;
    date: string;
    note: string | null;
    debt_id: number | null;
    from_account: AccountRef | null;
    to_account: AccountRef | null;
    category: CategoryRef | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

// ─── Props ────────────────────────────────────────────────────────────────────

defineProps<{
    transactions: Paginator;
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Transactions', href: '/transactions' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
}

/** Signed amount string with colour class */
function amountDisplay(tx: Transaction): { text: string; class: string } {
    const n = Number(tx.amount);
    const formatted = formatCurrency(n);

    if (tx.type === 'income') {
        return { text: `+${formatted}`, class: 'text-green-600 dark:text-green-400 font-semibold' };
    }
    if (tx.type === 'expense') {
        return { text: `-${formatted}`, class: 'text-red-600 dark:text-red-400 font-semibold' };
    }
    // transfer
    return { text: formatted, class: 'text-blue-600 dark:text-blue-400 font-semibold' };
}

/** Badge classes per transaction type */
const badgeClass: Record<string, string> = {
    income:   'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    expense:  'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    transfer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
};

// ─── Delete logic ─────────────────────────────────────────────────────────────

const { toast } = useToast();
const showDeleteDialog = ref(false);
const deletingTx = ref<Transaction | null>(null);

function confirmDelete(tx: Transaction) {
    deletingTx.value = tx;
    showDeleteDialog.value = true;
}

function executeDelete() {
    if (!deletingTx.value) return;

    router.delete(`/transactions/${deletingTx.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast({ title: 'Deleted', description: 'Transaction deleted and balance reversed.' });
        },
        onFinish: () => {
            showDeleteDialog.value = false;
            deletingTx.value = null;
        },
    });
}
</script>

<template>
    <Head title="Transactions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Page header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Transactions</h1>
                <p class="text-muted-foreground text-sm">Your full transaction history, newest first.</p>
            </div>

            <!-- Table card -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold">
                        All Transactions
                        <span class="text-muted-foreground ml-2 text-sm font-normal">
                            ({{ transactions.total }} total)
                        </span>
                    </CardTitle>
                </CardHeader>

                <CardContent class="p-0">

                    <!-- Empty state -->
                    <div
                        v-if="transactions.data.length === 0"
                        class="text-muted-foreground flex flex-col items-center justify-center gap-2 py-16 text-center"
                    >
                        <p class="text-sm">No transactions yet.</p>
                    </div>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/40 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3">From / To</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-right">Fee</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="tx in transactions.data"
                                    :key="tx.id"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <!-- Date -->
                                    <td class="whitespace-nowrap px-4 py-3 text-muted-foreground">
                                        {{ formatDate(tx.date) }}
                                    </td>

                                    <!-- Description -->
                                    <td class="px-4 py-3">
                                        <span v-if="tx.type === 'transfer' && tx.from_account && tx.to_account" class="flex items-center gap-1">
                                            {{ tx.from_account.name }}
                                            <ArrowRight class="h-3 w-3 shrink-0 text-muted-foreground" />
                                            {{ tx.to_account.name }}
                                        </span>
                                        <span v-else>{{ tx.note ?? '—' }}</span>
                                    </td>

                                    <!-- Type badge -->
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                            :class="badgeClass[tx.type]"
                                        >
                                            {{ tx.type }}
                                        </span>
                                    </td>

                                    <!-- Category -->
                                    <td class="px-4 py-3">
                                        <span class="flex items-center gap-1.5">
                                            <span
                                                v-if="tx.category?.color"
                                                class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                                                :style="{ backgroundColor: tx.category.color }"
                                            />
                                            <span class="text-muted-foreground">{{ tx.category?.name ?? '—' }}</span>
                                        </span>
                                    </td>

                                    <!-- From / To accounts -->
                                    <td class="px-4 py-3 text-muted-foreground">
                                        <span v-if="tx.type !== 'transfer'">
                                            {{ tx.from_account?.name ?? tx.to_account?.name ?? '—' }}
                                        </span>
                                        <span v-else class="text-xs">
                                            {{ tx.from_account?.name }} / {{ tx.to_account?.name }}
                                        </span>
                                    </td>

                                    <!-- Amount (coloured + signed) -->
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums" :class="amountDisplay(tx).class">
                                        {{ amountDisplay(tx).text }}
                                    </td>

                                    <!-- Fee -->
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-muted-foreground">
                                        {{ Number(tx.fee) > 0 ? formatCurrency(tx.fee) : '—' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 text-muted-foreground hover:text-destructive"
                                            @click="confirmDelete(tx)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="transactions.last_page > 1"
                        class="flex items-center justify-between border-t px-4 py-3"
                    >
                        <p class="text-muted-foreground text-xs">
                            Page {{ transactions.current_page }} of {{ transactions.last_page }}
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in transactions.links" :key="link.label">
                                <Button
                                    v-if="link.url"
                                    as-child
                                    size="sm"
                                    :variant="link.active ? 'default' : 'outline'"
                                    class="h-8 min-w-8 px-2 text-xs"
                                >
                                    <Link :href="link.url"><span v-html="link.label" /></Link>
                                </Button>
                                <Button
                                    v-else
                                    size="sm"
                                    variant="outline"
                                    disabled
                                    class="h-8 min-w-8 px-2 text-xs opacity-40"
                                ><span v-html="link.label" /></Button>
                            </template>
                        </div>
                    </div>

                </CardContent>
            </Card>

        </div>

        <!-- Delete confirmation dialog -->
        <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Transaction</AlertDialogTitle>
                    <AlertDialogDescription>
                        <template v-if="deletingTx?.debt_id">
                            This transaction is linked to a debt/loan record. Deleting it will also revert your debt record and reverse the balance change on the associated account(s). This action cannot be undone.
                        </template>
                        <template v-else>
                            Are you sure you want to delete this transaction? This will reverse the balance change on the associated account(s). This action cannot be undone.
                        </template>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="executeDelete"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
