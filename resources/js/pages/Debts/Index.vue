<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AccountOption, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowDownLeft,
    ArrowUpRight,
    CalendarDays,
    HandCoins,
    Mail,
    MessageCircle,
    Plus,
    Settings,
    Send,
    Wallet,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// ─── Types ────────────────────────────────────────────────────────────────────

type DebtStatus = 'pending' | 'partially_paid' | 'settled';
type DebtType   = 'borrowed' | 'lent';

interface DebtAccount {
    id: number;
    name: string;
    type: string;
}

interface Debt {
    id: number;
    person_name: string;
    type: DebtType;
    amount: string;
    remaining_amount: string;
    due_date: string | null;
    status: DebtStatus;
    description: string | null;
    account: DebtAccount;
    contact_email: string | null;
    contact_phone: string | null;
    initial_notification_sent: boolean;
    last_reminder_sent_at: string | null;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const { debts, totalOwed, totalReceivable } = defineProps<{
    debts: Debt[];
    totalOwed: number;
    totalReceivable: number;
}>();

// ─── Shared ───────────────────────────────────────────────────────────────────

const page       = usePage<SharedData>();
const allAccounts = computed<AccountOption[]>(() => page.props.userAccounts);

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Debts & Loans', href: '/debts' },
];

// ─── Create form ──────────────────────────────────────────────────────────────

const createOpen = ref(false);

const createForm = useForm({
    type:          'borrowed' as DebtType,
    person_name:   '',
    amount:        '',
    account_id:    '',
    contact_email: '',
    contact_phone: '',
    fee:         '',
    due_date:    '',
    description: '',
});

const isLending = computed(() => createForm.type === 'lent');

const totalDeduction = computed(() => {
    const amt  = Number.parseFloat(createForm.amount) || 0;
    const fee  = Number.parseFloat(createForm.fee)    || 0;
    return (amt + fee).toFixed(2);
});

const selectedAccountBalance = computed(() => {
    if (!createForm.account_id) return null;
    const acc = allAccounts.value.find(a => a.id === Number(createForm.account_id));
    return acc ? Number.parseFloat(acc.balance) : null;
});

const insufficientBalance = computed(() => {
    if (!isLending.value || selectedAccountBalance.value === null) return false;
    return Number.parseFloat(totalDeduction.value) > selectedAccountBalance.value;
});

function submitCreate() {
    createForm.post(route('debts.store'), {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

// Reset when dialog closes
watch(createOpen, (val) => {
    if (!val) createForm.reset();
});

// ─── Payment form ─────────────────────────────────────────────────────────────

const paymentOpen   = ref(false);
const paymentTarget = ref<Debt | null>(null);

const paymentForm = useForm({
    payment_amount: '',
    account_id:     '',
    fee:            '',
});

const paymentAccountBalance = computed(() => {
    if (!paymentForm.account_id) return null;
    const acc = allAccounts.value.find(a => a.id === Number(paymentForm.account_id));
    return acc ? Number.parseFloat(acc.balance) : null;
});

// Total that will leave/arrive at the account (payment + fee)
const paymentTotalDeduction = computed(() => {
    const amt = Number.parseFloat(paymentForm.payment_amount) || 0;
    const fee = Number.parseFloat(paymentForm.fee) || 0;
    return amt + fee;
});

const paymentInsufficient = computed(() => {
    if (!paymentTarget.value || paymentTarget.value.type !== 'borrowed') return false;
    if (paymentAccountBalance.value === null) return false;
    return paymentTotalDeduction.value > paymentAccountBalance.value;
});

const paymentExceedsRemaining = computed(() => {
    if (!paymentTarget.value) return false;
    const remaining = Number.parseFloat(paymentTarget.value.remaining_amount);
    const payment   = Number.parseFloat(paymentForm.payment_amount) || 0;
    return payment > remaining;
});

function openPayment(debt: Debt) {
    paymentTarget.value = debt;
    paymentForm.reset();
    paymentOpen.value = true;
}

function submitPayment() {
    if (!paymentTarget.value) return;
    paymentForm.post(route('debts.payment', { debt: paymentTarget.value.id }), {
        onSuccess: () => {
            paymentOpen.value = false;
            paymentTarget.value = null;
            paymentForm.reset();
        },
    });
}

watch(paymentOpen, (val) => {
    if (!val) {
        paymentTarget.value = null;
        paymentForm.reset();
    }
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

const fmt = (v: string | number) =>
    Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const statusBadge: Record<DebtStatus, string> = {
    pending:        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    partially_paid: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    settled:        'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
};

const statusLabel: Record<DebtStatus, string> = {
    pending:        'Pending',
    partially_paid: 'Partial',
    settled:        'Settled',
};

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function isDueSoon(d: string | null): boolean {
    if (!d) return false;
    const diff = new Date(d).getTime() - Date.now();
    return diff >= 0 && diff < 7 * 24 * 60 * 60 * 1000;
}

function isOverdue(d: string | null): boolean {
    if (!d) return false;
    return new Date(d).getTime() < Date.now();
}

// ─── Notification actions ─────────────────────────────────────────────────────

const notifyLoading = ref<number | null>(null);
const remindLoading = ref<number | null>(null);

async function sendNotify(debt: Debt) {
    notifyLoading.value = debt.id;
    try {
        const res = await fetch(route('debts.notify', { debt: debt.id }), {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            router.reload({ only: ['debts'] });
        }
    } catch {} finally {
        notifyLoading.value = null;
    }
}

async function sendRemind(debt: Debt) {
    remindLoading.value = debt.id;
    try {
        const res = await fetch(route('debts.remind', { debt: debt.id }), {
            method: 'POST',
            headers: {
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            router.reload({ only: ['debts'] });
        }
    } catch {} finally {
        remindLoading.value = null;
    }
}

async function openWhatsApp(debt: Debt, type: 'initial' | 'reminder') {
    try {
        const res = await fetch(route('debts.whatsappLink', { debt: debt.id }) + `?type=${type}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.link) {
            window.open(data.link, '_blank');
        }
    } catch {}
}
</script>

<template>
    <Head title="Debts & Loans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Page header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Debts &amp; Loans</h1>
                    <p class="text-muted-foreground text-sm">Track money you owe and money owed to you.</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Settings -->
                    <Button variant="outline" size="sm" class="gap-1.5" as="a" href="/debts/settings">
                        <Settings class="h-4 w-4" />
                        <span class="hidden sm:inline">Settings</span>
                    </Button>

                    <!-- Create dialog trigger -->
                    <Dialog v-model:open="createOpen">
                    <DialogTrigger as-child>
                        <Button size="sm" class="gap-2">
                            <Plus class="h-4 w-4" />
                            Record Debt / Loan
                        </Button>
                    </DialogTrigger>

                    <DialogContent class="max-w-[95vw] sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2">
                                <HandCoins class="h-5 w-5" />
                                Record Debt or Loan
                            </DialogTitle>
                        </DialogHeader>

                        <form @submit.prevent="submitCreate" class="grid gap-4 py-2">

                            <!-- Borrow / Lend toggle -->
                            <div class="grid gap-1.5">
                                <span class="text-sm font-medium leading-none">Type</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        class="flex items-center justify-center gap-2 rounded-md border-2 px-4 py-2.5 text-sm font-medium transition-colors"
                                        :class="createForm.type === 'borrowed'
                                            ? 'border-orange-500 bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300'
                                            : 'border-border hover:bg-muted/50'"
                                        @click="createForm.type = 'borrowed'"
                                    >
                                        <ArrowDownLeft class="h-4 w-4" />
                                        I Borrowed
                                    </button>
                                    <button
                                        type="button"
                                        class="flex items-center justify-center gap-2 rounded-md border-2 px-4 py-2.5 text-sm font-medium transition-colors"
                                        :class="createForm.type === 'lent'
                                            ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'
                                            : 'border-border hover:bg-muted/50'"
                                        @click="createForm.type = 'lent'"
                                    >
                                        <ArrowUpRight class="h-4 w-4" />
                                        I Lent
                                    </button>
                                </div>
                            </div>

                            <!-- Person name -->
                            <div class="grid gap-1.5">
                                <Label for="c-person">{{ createForm.type === 'borrowed' ? 'Borrowed from' : 'Lent to' }}</Label>
                                <Input
                                    id="c-person"
                                    v-model="createForm.person_name"
                                    placeholder="Person or organization name"
                                    required
                                />
                                <p v-if="createForm.errors.person_name" class="text-destructive text-xs">{{ createForm.errors.person_name }}</p>
                            </div>

                            <!-- Amount + Fee (side by side when lent) -->
                            <div class="grid gap-4" :class="isLending ? 'grid-cols-2' : 'grid-cols-1'">
                                <div class="grid gap-1.5">
                                    <Label for="c-amount">Amount (Rs.)</Label>
                                    <Input
                                        id="c-amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        v-model="createForm.amount"
                                        placeholder="0.00"
                                        required
                                    />
                                    <p v-if="createForm.errors.amount" class="text-destructive text-xs">{{ createForm.errors.amount }}</p>
                                </div>

                                <!-- Fee — only for lending -->
                                <div v-if="isLending" class="grid gap-1.5">
                                    <Label for="c-fee">Bank Fee (Rs.)</Label>
                                    <Input
                                        id="c-fee"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        v-model="createForm.fee"
                                        placeholder="0.00"
                                    />
                                    <p v-if="createForm.errors.fee" class="text-destructive text-xs">{{ createForm.errors.fee }}</p>
                                </div>
                            </div>

                            <!-- Total deduction hint (lent only) -->
                            <div
                                v-if="isLending && (parseFloat(createForm.amount) > 0 || parseFloat(createForm.fee) > 0)"
                                class="rounded-md px-3 py-2 text-sm"
                                :class="insufficientBalance
                                    ? 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300'
                                    : 'bg-muted text-muted-foreground'"
                            >
                                <span v-if="insufficientBalance">
                                    ⚠ Insufficient balance. Total deduction
                                    <strong>Rs. {{ totalDeduction }}</strong>
                                    exceeds available balance
                                    <strong>Rs. {{ fmt(selectedAccountBalance ?? 0) }}</strong>.
                                </span>
                                <span v-else>
                                    Total will be deducted from your account:
                                    <strong>Rs. {{ totalDeduction }}</strong>
                                </span>
                            </div>

                            <!-- Account -->
                            <div class="grid gap-1.5">
                                <Label for="c-account">{{ createForm.type === 'borrowed' ? 'Receive into Account' : 'Deduct from Account' }}</Label>
                                <select
                                    id="c-account"
                                    v-model="createForm.account_id"
                                    required
                                    class="border-input bg-background text-sm ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="" disabled>Select account…</option>
                                    <option v-for="acc in allAccounts" :key="acc.id" :value="acc.id">
                                        {{ acc.name }} — Rs. {{ fmt(acc.balance) }}
                                    </option>
                                </select>
                                <p v-if="allAccounts.length === 0" class="text-muted-foreground text-xs">No accounts found. Add one in Accounts.</p>
                                <p v-if="createForm.errors.account_id" class="text-destructive text-xs">{{ createForm.errors.account_id }}</p>
                            </div>

                            <!-- Due date -->
                            <div class="grid gap-1.5">
                                <Label for="c-due">Due Date <span class="text-muted-foreground font-normal">(optional)</span></Label>
                                <Input id="c-due" type="date" v-model="createForm.due_date" />
                                <p v-if="createForm.errors.due_date" class="text-destructive text-xs">{{ createForm.errors.due_date }}</p>
                            </div>

                            <!-- Description -->
                            <div class="grid gap-1.5">
                                <Label for="c-desc">Description <span class="text-muted-foreground font-normal">(optional)</span></Label>
                                <Input id="c-desc" v-model="createForm.description" placeholder="Notes…" />
                                <p v-if="createForm.errors.description" class="text-destructive text-xs">{{ createForm.errors.description }}</p>
                            </div>

                            <!-- Contact info (for lending — to send notifications) -->
                            <div v-if="isLending" class="rounded-md border border-blue-200 bg-blue-50/50 p-3 dark:border-blue-800 dark:bg-blue-950/20">
                                <p class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-300">
                                    <Send class="h-3.5 w-3.5" />
                                    Borrower Contact (for notifications)
                                </p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="grid gap-1.5">
                                        <Label for="c-email" class="text-xs">Email</Label>
                                        <Input id="c-email" type="email" v-model="createForm.contact_email" placeholder="person@example.com" />
                                        <p v-if="createForm.errors.contact_email" class="text-destructive text-xs">{{ createForm.errors.contact_email }}</p>
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="c-phone" class="text-xs">WhatsApp Number</Label>
                                        <Input id="c-phone" v-model="createForm.contact_phone" placeholder="+94771234567" />
                                        <p v-if="createForm.errors.contact_phone" class="text-destructive text-xs">{{ createForm.errors.contact_phone }}</p>
                                    </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
                                <Button
                                    type="submit"
                                    :disabled="createForm.processing || (isLending && insufficientBalance)"
                                    :class="createForm.type === 'lent' ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-orange-500 hover:bg-orange-600 text-white'"
                                >
                                    {{ createForm.type === 'borrowed' ? 'Record Debt' : 'Record Loan' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Total Owed (borrowed) -->
                <Card class="border-orange-200 dark:border-orange-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">I Owe (Borrowed)</CardTitle>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/40">
                            <ArrowDownLeft class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            Rs. {{ fmt(totalOwed) }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-xs">Outstanding borrowed amounts</p>
                    </CardContent>
                </Card>

                <!-- Total Receivable (lent) -->
                <Card class="border-blue-200 dark:border-blue-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Owed to Me (Lent)</CardTitle>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                            <ArrowUpRight class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            Rs. {{ fmt(totalReceivable) }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-xs">Outstanding amounts to collect</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Debts table -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold">
                        All Debts &amp; Loans
                        <span class="text-muted-foreground ml-2 text-sm font-normal">({{ debts.length }} total)</span>
                    </CardTitle>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="debts.length === 0" class="text-muted-foreground flex flex-col items-center justify-center gap-2 py-16 text-center">
                        <HandCoins class="h-8 w-8 opacity-30" />
                        <p class="text-sm">No debts or loans recorded yet.</p>
                    </div>

                    <template v-else>
                    <!-- Mobile card list (< md) -->
                    <div class="flex flex-col gap-3 p-4 md:hidden">
                        <div
                            v-for="debt in debts"
                            :key="'m-' + debt.id"
                            class="rounded-xl border bg-card p-4 shadow-sm"
                            :class="debt.status === 'settled' ? 'opacity-60' : ''"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xs font-semibold"
                                        :class="debt.type === 'borrowed'
                                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
                                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'"
                                    >
                                        <ArrowDownLeft v-if="debt.type === 'borrowed'" class="h-4 w-4" />
                                        <ArrowUpRight v-else class="h-4 w-4" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ debt.person_name }}</p>
                                        <p class="truncate text-xs text-muted-foreground">
                                            {{ debt.type === 'borrowed' ? 'Borrowed' : 'Lent' }} · {{ debt.account.name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-bold tabular-nums" :class="debt.status === 'settled' ? 'text-green-600 dark:text-green-400' : ''">
                                        Rs. {{ fmt(debt.remaining_amount) }}
                                    </p>
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="statusBadge[debt.status]"
                                    >
                                        {{ statusLabel[debt.status] }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="debt.due_date && debt.status !== 'settled'" class="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                                <CalendarDays class="h-3 w-3" />
                                <span :class="isOverdue(debt.due_date) ? 'text-red-600 dark:text-red-400 font-semibold' : isDueSoon(debt.due_date) ? 'text-yellow-600 dark:text-yellow-400' : ''">
                                    {{ formatDate(debt.due_date) }}
                                    <template v-if="isOverdue(debt.due_date)"> (Overdue)</template>
                                    <template v-else-if="isDueSoon(debt.due_date)"> (Soon)</template>
                                </span>
                            </div>
                            <div v-if="debt.status !== 'settled'" class="mt-3 flex flex-col gap-1.5">
                                <Button size="sm" variant="outline" class="h-8 w-full gap-1 text-xs" @click="openPayment(debt)">
                                    <Plus class="h-3 w-3" />
                                    Add Payment
                                </Button>
                                <!-- Notify / Remind buttons for lent debts with contact info -->
                                <div v-if="debt.type === 'lent' && (debt.contact_email || debt.contact_phone)" class="flex gap-1.5">
                                    <Button
                                        v-if="debt.contact_email"
                                        size="sm" variant="outline"
                                        class="h-7 flex-1 gap-1 text-[10px]"
                                        :disabled="notifyLoading === debt.id || remindLoading === debt.id"
                                        @click="debt.initial_notification_sent ? sendRemind(debt) : sendNotify(debt)"
                                    >
                                        <Mail class="h-3 w-3" />
                                        {{ debt.initial_notification_sent ? 'Remind' : 'Notify' }}
                                    </Button>
                                    <Button
                                        v-if="debt.contact_phone"
                                        size="sm" variant="outline"
                                        class="h-7 flex-1 gap-1 text-[10px] text-green-600"
                                        @click="openWhatsApp(debt, debt.initial_notification_sent ? 'reminder' : 'initial')"
                                    >
                                        <MessageCircle class="h-3 w-3" />
                                        WhatsApp
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop table (>= md) -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/40 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    <th class="px-4 py-3">Person</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="hidden px-4 py-3 lg:table-cell">Account</th>
                                    <th class="hidden px-4 py-3 text-right lg:table-cell">Amount</th>
                                    <th class="px-4 py-3 text-right">Remaining</th>
                                    <th class="hidden px-4 py-3 lg:table-cell">Due</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="debt in debts"
                                    :key="debt.id"
                                    class="transition-colors hover:bg-muted/30"
                                    :class="debt.status === 'settled' ? 'opacity-60' : ''"
                                >
                                    <td class="px-4 py-3 font-medium">{{ debt.person_name }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="debt.type === 'borrowed'
                                                ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
                                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'"
                                        >
                                            <ArrowDownLeft v-if="debt.type === 'borrowed'" class="h-3 w-3" />
                                            <ArrowUpRight v-else class="h-3 w-3" />
                                            {{ debt.type === 'borrowed' ? 'Borrowed' : 'Lent' }}
                                        </span>
                                    </td>
                                    <td class="hidden text-muted-foreground px-4 py-3 lg:table-cell">
                                        <span class="flex items-center gap-1.5">
                                            <Wallet class="h-3.5 w-3.5" />
                                            {{ debt.account.name }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-3 text-right font-mono lg:table-cell">
                                        Rs. {{ fmt(debt.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono">
                                        <span :class="debt.status === 'settled' ? 'text-green-600 dark:text-green-400' : 'font-semibold'">
                                            Rs. {{ fmt(debt.remaining_amount) }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-3 lg:table-cell">
                                        <span
                                            v-if="debt.due_date && debt.status !== 'settled'"
                                            class="inline-flex items-center gap-1 text-xs"
                                            :class="isOverdue(debt.due_date) ? 'text-red-600 dark:text-red-400 font-semibold' : isDueSoon(debt.due_date) ? 'text-yellow-600 dark:text-yellow-400' : 'text-muted-foreground'"
                                        >
                                            <CalendarDays class="h-3 w-3" />
                                            {{ formatDate(debt.due_date) }}
                                            <span v-if="isOverdue(debt.due_date)" class="ml-0.5">(Overdue)</span>
                                            <span v-else-if="isDueSoon(debt.due_date)" class="ml-0.5">(Soon)</span>
                                        </span>
                                        <span v-else class="text-muted-foreground text-xs">{{ formatDate(debt.due_date) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusBadge[debt.status]"
                                        >
                                            {{ statusLabel[debt.status] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <Button
                                                v-if="debt.status !== 'settled'"
                                                size="sm"
                                                variant="outline"
                                                class="h-7 gap-1 text-xs"
                                                @click="openPayment(debt)"
                                            >
                                                <Plus class="h-3 w-3" />
                                                Add Payment
                                            </Button>
                                            <span v-if="debt.status === 'settled'" class="text-muted-foreground text-xs italic">Settled</span>

                                            <template v-if="debt.type === 'lent' && debt.status !== 'settled' && (debt.contact_email || debt.contact_phone)">
                                                <Button
                                                    v-if="debt.contact_email"
                                                    size="sm"
                                                    variant="ghost"
                                                    class="h-7 gap-1 text-xs"
                                                    :title="debt.initial_notification_sent ? 'Resend notification' : 'Send initial notification'"
                                                    @click="sendNotify(debt)"
                                                >
                                                    <Mail class="h-3 w-3" />
                                                    {{ debt.initial_notification_sent ? 'Remind' : 'Notify' }}
                                                </Button>
                                                <Button
                                                    v-if="debt.contact_phone"
                                                    size="sm"
                                                    variant="ghost"
                                                    class="h-7 gap-1 text-xs text-green-600 dark:text-green-400"
                                                    title="Send WhatsApp message"
                                                    @click="openWhatsApp(debt)"
                                                >
                                                    <MessageCircle class="h-3 w-3" />
                                                    WhatsApp
                                                </Button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </template>
                </CardContent>
            </Card>

        </div>
    </AppLayout>

    <!-- ─── Add Payment Dialog ────────────────────────────────────────────────── -->
    <Dialog v-model:open="paymentOpen">
        <DialogContent class="max-w-[95vw] sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <HandCoins class="h-5 w-5" />
                    Add Payment
                </DialogTitle>
            </DialogHeader>

            <div v-if="paymentTarget" class="rounded-md bg-muted/50 px-3 py-2 text-sm">
                <p>
                    <span class="text-muted-foreground">Person: </span>
                    <strong>{{ paymentTarget.person_name }}</strong>
                </p>
                <p>
                    <span class="text-muted-foreground">Remaining: </span>
                    <strong>Rs. {{ fmt(paymentTarget.remaining_amount) }}</strong>
                </p>
                <p class="text-muted-foreground mt-0.5 text-xs">
                    {{ paymentTarget.type === 'borrowed'
                        ? 'You are repaying this debt. Amount + fee will be deducted from your account.'
                        : 'You are receiving a repayment. Amount will be added to your account.' }}
                </p>
            </div>

            <form @submit.prevent="submitPayment" class="grid gap-4">
                <!-- Payment amount + Fee side by side -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1.5">
                        <Label for="p-amount">Payment (Rs.)</Label>
                        <Input
                            id="p-amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            v-model="paymentForm.payment_amount"
                            placeholder="0.00"
                            required
                        />
                        <p v-if="paymentExceedsRemaining" class="text-destructive text-xs">
                            Exceeds remaining Rs. {{ fmt(paymentTarget?.remaining_amount ?? 0) }}.
                        </p>
                        <p v-if="paymentForm.errors.payment_amount" class="text-destructive text-xs">{{ paymentForm.errors.payment_amount }}</p>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="p-fee">
                            Bank Fee (Rs.)
                            <span class="font-normal text-muted-foreground">(opt.)</span>
                        </Label>
                        <Input
                            id="p-fee"
                            type="number"
                            min="0"
                            step="0.01"
                            v-model="paymentForm.fee"
                            placeholder="0.00"
                        />
                        <p v-if="paymentForm.errors.fee" class="text-destructive text-xs">{{ paymentForm.errors.fee }}</p>
                    </div>
                </div>

                <!-- Total Deduction Banner -->
                <div
                    v-if="paymentTotalDeduction > 0"
                    class="flex items-center justify-between rounded-lg border px-4 py-3 text-sm font-medium"
                    :class="paymentInsufficient
                        ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300'
                        : 'border-primary/20 bg-primary/5 text-foreground'"
                >
                    <span>
                        <template v-if="paymentInsufficient">⚠ Insufficient balance</template>
                        <template v-else-if="paymentTarget?.type === 'borrowed'">Total to be deducted</template>
                        <template v-else>Net amount received</template>
                    </span>
                    <span class="text-base font-bold tabular-nums">
                        Rs. {{ paymentTotalDeduction.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </span>
                </div>

                <!-- Account selection -->
                <div class="grid gap-1.5">
                    <Label for="p-account">{{ paymentTarget?.type === 'borrowed' ? 'Pay from Account' : 'Receive into Account' }}</Label>
                    <select
                        id="p-account"
                        v-model="paymentForm.account_id"
                        required
                        class="border-input bg-background text-sm ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="" disabled>Select account…</option>
                        <option v-for="acc in allAccounts" :key="acc.id" :value="acc.id">
                            {{ acc.name }} — Rs. {{ fmt(acc.balance) }}
                        </option>
                    </select>
                    <p v-if="paymentInsufficient && paymentTarget?.type === 'borrowed'" class="text-destructive text-xs">
                        Insufficient balance. Need Rs. {{ paymentTotalDeduction.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}, available Rs. {{ fmt(paymentAccountBalance ?? 0) }}.
                    </p>
                    <p v-if="paymentForm.errors.account_id" class="text-destructive text-xs">{{ paymentForm.errors.account_id }}</p>
                </div>

                <!-- Warning if overdue -->
                <div
                    v-if="paymentTarget?.due_date && isOverdue(paymentTarget.due_date)"
                    class="flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300"
                >
                    <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    This debt is overdue since {{ formatDate(paymentTarget.due_date) }}.
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="paymentOpen = false" :disabled="paymentForm.processing">Cancel</Button>
                    <Button
                        type="submit"
                        :disabled="paymentForm.processing || paymentExceedsRemaining || paymentInsufficient"
                    >
                        {{ paymentForm.processing ? 'Processing…' : 'Confirm Payment' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
