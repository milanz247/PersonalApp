<script setup lang="ts">
/**
 * TransactionCard — Mobile-friendly card for a single transaction.
 * Replaces table rows on small screens.
 */

export interface TransactionCardProps {
    type: 'income' | 'expense' | 'transfer';
    amount: string;
    categoryName?: string;
    accountName?: string;
    note?: string;
    date: string;
}

const props = defineProps<TransactionCardProps>();

const typeStyle: Record<string, { badge: string; sign: string }> = {
    income:   { badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300', sign: '+' },
    expense:  { badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', sign: '-' },
    transfer: { badge: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', sign: '' },
};

const style = typeStyle[props.type] ?? typeStyle.expense;
</script>

<template>
    <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm active:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:active:bg-zinc-800">
        <!-- Left: type badge + details -->
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xs font-semibold"
                :class="style.badge"
            >
                {{ type.charAt(0).toUpperCase() }}
            </span>
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ categoryName ?? note ?? 'Transaction' }}
                </p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                    {{ accountName }} &middot; {{ date }}
                </p>
            </div>
        </div>
        <!-- Right: amount -->
        <span
            class="shrink-0 text-sm font-semibold tabular-nums"
            :class="{
                'text-green-600 dark:text-green-400': type === 'income',
                'text-red-600 dark:text-red-400': type === 'expense',
                'text-blue-600 dark:text-blue-400': type === 'transfer',
            }"
        >
            {{ style.sign }}{{ amount }}
        </span>
    </div>
</template>
