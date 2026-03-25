<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import InputError from '@/components/InputError.vue';
import { ArrowLeftRight, Building2, Ellipsis, Pencil, PlusCircle, Trash2, TrendingUp, Wallet } from 'lucide-vue-next';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Account {
    id: number;
    name: string;
    type: 'wallet' | 'bank';
    bank_name: string | null;
    branch_name: string | null;
    account_number: string | null;
    balance: string;
    created_at: string;
}

interface AccountSummary {
    id: number;
    name: string;
    type: 'wallet' | 'bank';
    balance: string;
}

const props = defineProps<{
    wallet: Account | null;
    bankAccounts: Account[];
    allAccounts: AccountSummary[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Accounts', href: '/accounts' },
];

const page = usePage<SharedData>();

const dialogOpen = ref(false);
const transferOpen = ref(false);
const incomeOpen = ref(false);
const editOpen = ref(false);
const deleteOpen = ref(false);
const targetAccount = ref<Account | null>(null);

const form = useForm({
    name: '',
    bank_name: '',
    branch_name: '',
    account_number: '',
    balance: '',
});

const transferForm = useForm({
    from_account_id: '',
    to_account_id: '',
    amount: '',
    fee: '',
    date: new Date().toISOString().slice(0, 10),
    note: '',
});

const incomeForm = useForm({
    type: 'income',
    amount: '',
    category_id: '',
    to_account_id: '',
    date: new Date().toISOString().slice(0, 10),
    note: '',
});

const editForm = useForm({
    name: '',
    bank_name: '',
    branch_name: '',
    account_number: '',
});

// Show the balance of the selected source account inside the transfer modal
const fromAccountBalance = computed(() => {
    if (!transferForm.from_account_id) return null;
    const found = props.allAccounts.find((a) => String(a.id) === String(transferForm.from_account_id));
    return found ? found.balance : null;
});

function submitForm() {
    form.post(route('accounts.store'), {
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
}

function submitTransfer() {
    transferForm.post(route('transfers.store'), {
        onSuccess: () => {
            transferOpen.value = false;
            transferForm.reset();
            transferForm.date = new Date().toISOString().slice(0, 10);
        },
    });
}

function submitIncome() {
    incomeForm.post(route('transactions.store'), {
        onSuccess: () => {
            incomeOpen.value = false;
            incomeForm.reset();
            incomeForm.date = new Date().toISOString().slice(0, 10);
        },
    });
}

function formatCurrency(value: string | number) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}

function openEdit(account: Account) {
    targetAccount.value = account;
    editForm.name = account.name;
    editForm.bank_name = account.bank_name ?? '';
    editForm.branch_name = account.branch_name ?? '';
    editForm.account_number = account.account_number ?? '';
    editOpen.value = true;
}

function submitEdit() {
    if (!targetAccount.value) return;
    editForm.put(route('accounts.update', { account: targetAccount.value.id }), {
        onSuccess: () => {
            editOpen.value = false;
            editForm.reset();
            targetAccount.value = null;
        },
    });
}

function openDelete(account: Account) {
    targetAccount.value = account;
    deleteOpen.value = true;
}

function confirmDelete() {
    if (!targetAccount.value) return;
    router.delete(route('accounts.destroy', { account: targetAccount.value.id }), {
        onSuccess: () => {
            deleteOpen.value = false;
            targetAccount.value = null;
        },
        onError: () => {
            deleteOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Accounts</h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage your wallet and bank accounts.</p>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                    <!-- Income Dialog -->
                    <Dialog v-model:open="incomeOpen">
                        <DialogTrigger as-child>
                            <Button class="w-full gap-1.5 bg-green-600 text-white hover:bg-green-700 sm:w-auto" size="sm">
                                <TrendingUp class="h-4 w-4" />
                                + Income
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="max-w-[95vw] sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Add Income</DialogTitle>
                                <DialogDescription>Record income received into one of your accounts.</DialogDescription>
                            </DialogHeader>

                            <!-- Empty-state warning -->
                            <div
                                v-if="page.props.incomeCategories.length === 0 || allAccounts.length === 0"
                                class="rounded-md bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                            >
                                <span v-if="page.props.incomeCategories.length === 0">
                                    No income categories yet.
                                    <a href="/categories" class="underline">Create one first.</a>
                                </span>
                                <span v-else>No accounts found. Please create an account first.</span>
                            </div>

                            <form v-else class="grid gap-4 py-2" @submit.prevent="submitIncome">
                                <!-- Amount -->
                                <div class="grid gap-1.5">
                                    <Label for="inc-amount">Amount</Label>
                                    <Input
                                        id="inc-amount"
                                        v-model="incomeForm.amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="0.00"
                                        required
                                    />
                                    <InputError :message="incomeForm.errors.amount" />
                                </div>

                                <!-- Category -->
                                <div class="grid gap-1.5">
                                    <Label for="inc-category">Category</Label>
                                    <select
                                        id="inc-category"
                                        v-model="incomeForm.category_id"
                                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        required
                                    >
                                        <option value="" disabled>Select a category</option>
                                        <option v-for="cat in page.props.incomeCategories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <InputError :message="incomeForm.errors.category_id" />
                                </div>

                                <!-- To Account -->
                                <div class="grid gap-1.5">
                                    <Label for="inc-account">To Account</Label>
                                    <select
                                        id="inc-account"
                                        v-model="incomeForm.to_account_id"
                                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        required
                                    >
                                        <option value="" disabled>Select account</option>
                                        <option v-for="acc in allAccounts" :key="acc.id" :value="acc.id">
                                            {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                                        </option>
                                    </select>
                                    <InputError :message="incomeForm.errors.to_account_id" />
                                </div>

                                <!-- Date -->
                                <div class="grid gap-1.5">
                                    <Label for="inc-date">Date</Label>
                                    <Input id="inc-date" v-model="incomeForm.date" type="date" required />
                                    <InputError :message="incomeForm.errors.date" />
                                </div>

                                <!-- Description -->
                                <div class="grid gap-1.5">
                                    <Label for="inc-note">
                                        Description
                                        <span class="font-normal text-muted-foreground">(optional)</span>
                                    </Label>
                                    <Input id="inc-note" v-model="incomeForm.note" placeholder="e.g. Monthly salary" />
                                    <InputError :message="incomeForm.errors.note" />
                                </div>

                                <DialogFooter class="pt-2">
                                    <Button type="button" variant="outline" @click="incomeOpen = false">Cancel</Button>
                                    <Button
                                        type="submit"
                                        class="bg-green-600 text-white hover:bg-green-700"
                                        :disabled="incomeForm.processing"
                                    >
                                        {{ incomeForm.processing ? 'Saving…' : 'Add Income' }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <!-- Transfer Dialog -->
                    <Dialog v-model:open="transferOpen">
                        <DialogTrigger as-child>
                            <Button class="w-full gap-1.5 sm:w-auto" size="sm" variant="outline">
                                <ArrowLeftRight class="h-4 w-4" />
                                Transfer
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="max-w-[95vw] sm:max-w-md">
                            <DialogHeader>
                                <DialogTitle>Internal Transfer</DialogTitle>
                                <DialogDescription>
                                    Move funds between your accounts. Fees are deducted from the source.
                                </DialogDescription>
                            </DialogHeader>

                            <form class="grid gap-4 py-2" @submit.prevent="submitTransfer">
                                <!-- From Account -->
                                <div class="grid gap-1.5">
                                    <Label for="from_account_id">From Account</Label>
                                    <select
                                        id="from_account_id"
                                        v-model="transferForm.from_account_id"
                                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select source account</option>
                                        <option
                                            v-for="acc in allAccounts"
                                            :key="acc.id"
                                            :value="acc.id"
                                        >
                                            {{ acc.name }} ({{ formatCurrency(acc.balance) }})
                                        </option>
                                    </select>
                                    <InputError :message="transferForm.errors.from_account_id" />

                                    <!-- Live balance hint -->
                                    <p
                                        v-if="fromAccountBalance !== null"
                                        class="text-muted-foreground text-xs"
                                    >
                                        Available: <span class="font-semibold text-foreground">{{ formatCurrency(fromAccountBalance) }}</span>
                                    </p>
                                </div>

                                <!-- To Account -->
                                <div class="grid gap-1.5">
                                    <Label for="to_account_id">To Account</Label>
                                    <select
                                        id="to_account_id"
                                        v-model="transferForm.to_account_id"
                                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="" disabled>Select destination account</option>
                                        <option
                                            v-for="acc in allAccounts"
                                            :key="acc.id"
                                            :value="acc.id"
                                            :disabled="String(acc.id) === String(transferForm.from_account_id)"
                                        >
                                            {{ acc.name }}
                                        </option>
                                    </select>
                                    <InputError :message="transferForm.errors.to_account_id" />
                                </div>

                                <!-- Amount -->
                                <div class="grid gap-1.5">
                                    <Label for="transfer_amount">Amount</Label>
                                    <Input
                                        id="transfer_amount"
                                        v-model="transferForm.amount"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                    <InputError :message="transferForm.errors.amount" />
                                </div>

                                <!-- Fee (optional) -->
                                <div class="grid gap-1.5">
                                    <Label for="transfer_fee">Fee <span class="text-muted-foreground">(optional)</span></Label>
                                    <Input
                                        id="transfer_fee"
                                        v-model="transferForm.fee"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                    />
                                    <InputError :message="transferForm.errors.fee" />
                                </div>

                                <!-- Date -->
                                <div class="grid gap-1.5">
                                    <Label for="transfer_date">Date</Label>
                                    <Input
                                        id="transfer_date"
                                        v-model="transferForm.date"
                                        type="date"
                                    />
                                    <InputError :message="transferForm.errors.date" />
                                </div>

                                <!-- Note -->
                                <div class="grid gap-1.5">
                                    <Label for="transfer_note">Note <span class="text-muted-foreground">(optional)</span></Label>
                                    <Input
                                        id="transfer_note"
                                        v-model="transferForm.note"
                                        placeholder="e.g. Monthly savings"
                                        autocomplete="off"
                                    />
                                    <InputError :message="transferForm.errors.note" />
                                </div>

                                <DialogFooter class="pt-2">
                                    <Button type="button" variant="outline" @click="transferOpen = false">
                                        Cancel
                                    </Button>
                                    <Button type="submit" :disabled="transferForm.processing">
                                        {{ transferForm.processing ? 'Processing…' : 'Confirm Transfer' }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <!-- Add Bank Account Dialog Trigger -->
                    <Dialog v-model:open="dialogOpen">
                        <DialogTrigger as-child>
                            <Button class="col-span-2 w-full gap-1.5 sm:w-auto" size="sm">
                                <PlusCircle class="h-4 w-4" />
                                Add Bank Account
                            </Button>
                        </DialogTrigger>

                    <DialogContent class="max-w-[95vw] sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Add Bank Account</DialogTitle>
                            <DialogDescription>
                                Fill in the details for your new bank account.
                            </DialogDescription>
                        </DialogHeader>

                        <form class="grid gap-4 py-2" @submit.prevent="submitForm">
                            <!-- Account Name -->
                            <div class="grid gap-1.5">
                                <Label for="name">Account Label</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g. My Savings Account"
                                    autocomplete="off"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Bank Name -->
                            <div class="grid gap-1.5">
                                <Label for="bank_name">Bank Name</Label>
                                <Input
                                    id="bank_name"
                                    v-model="form.bank_name"
                                    placeholder="e.g. National Bank"
                                    autocomplete="off"
                                />
                                <InputError :message="form.errors.bank_name" />
                            </div>

                            <!-- Branch Name -->
                            <div class="grid gap-1.5">
                                <Label for="branch_name">Branch Name</Label>
                                <Input
                                    id="branch_name"
                                    v-model="form.branch_name"
                                    placeholder="e.g. Downtown Branch"
                                    autocomplete="off"
                                />
                                <InputError :message="form.errors.branch_name" />
                            </div>

                            <!-- Account Number -->
                            <div class="grid gap-1.5">
                                <Label for="account_number">Account Number</Label>
                                <Input
                                    id="account_number"
                                    v-model="form.account_number"
                                    placeholder="e.g. 1234567890"
                                    autocomplete="off"
                                />
                                <InputError :message="form.errors.account_number" />
                            </div>

                            <!-- Initial Balance -->
                            <div class="grid gap-1.5">
                                <Label for="balance">Initial Balance</Label>
                                <Input
                                    id="balance"
                                    v-model="form.balance"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                />
                                <InputError :message="form.errors.balance" />
                            </div>

                            <DialogFooter class="pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="dialogOpen = false"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Saving…' : 'Save Account' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Main Wallet Card -->
            <div>
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                    Main Wallet
                </h2>
                <Card
                    v-if="wallet"
                    class="bg-gradient-to-br from-primary/90 to-primary text-primary-foreground shadow-md"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-lg font-semibold">{{ wallet.name }}</CardTitle>
                        <Wallet class="h-6 w-6 opacity-80" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-bold tracking-tight">
                            {{ formatCurrency(wallet.balance) }}
                        </p>
                        <p class="mt-1 text-sm opacity-75">Available Balance</p>
                    </CardContent>
                </Card>

                <Card v-else class="border-dashed">
                    <CardContent class="flex items-center gap-3 py-6 text-muted-foreground">
                        <Wallet class="h-5 w-5" />
                        <span class="text-sm">No wallet found for this account.</span>
                    </CardContent>
                </Card>
            </div>

            <!-- Bank Accounts Section -->
            <div class="mt-2">
                <h2 class="mb-4 text-xs font-semibold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                    Bank Accounts
                </h2>

                <!-- Empty State -->
                <Card v-if="bankAccounts.length === 0" class="border-dashed">
                    <CardContent class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                        <Building2 class="h-10 w-10 text-muted-foreground/50" />
                        <div>
                            <p class="font-medium text-muted-foreground">No bank accounts yet</p>
                            <p class="text-sm text-muted-foreground/70">
                                Click "Add Bank Account" to link your first bank account.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bank Account Cards Grid -->
                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="account in bankAccounts"
                        :key="account.id"
                        class="rounded-xl shadow-sm transition-shadow hover:shadow-md"
                    >
                        <CardHeader class="flex flex-row items-start justify-between pb-2">
                            <div class="space-y-1">
                                <CardTitle class="text-base font-bold">{{ account.name }}</CardTitle>
                                <CardDescription class="text-xs text-zinc-500 dark:text-zinc-400">{{ account.bank_name ?? 'Bank Account' }}</CardDescription>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <!-- Bank initials badge -->
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <span class="text-sm font-bold uppercase">
                                        {{ (account.bank_name ?? account.name).slice(0, 2) }}
                                    </span>
                                </div>
                                <!-- Actions dropdown -->
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 shrink-0 text-muted-foreground">
                                            <Ellipsis class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem @click="openEdit(account)">
                                            <Pencil class="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="text-red-600 focus:text-red-600 dark:text-red-400 dark:focus:text-red-400"
                                            @click="openDelete(account)"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <p class="text-2xl font-bold tracking-tight">
                                {{ formatCurrency(account.balance) }}
                            </p>
                            <div class="space-y-1 pt-2 text-xs">
                                <p>
                                    <span class="font-semibold text-foreground">Branch: </span>
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ account.branch_name ?? '—' }}</span>
                                </p>
                                <p>
                                    <span class="font-semibold text-foreground">Acc No: </span>
                                    <span class="font-mono text-zinc-500 dark:text-zinc-400">{{ account.account_number ?? '—' }}</span>
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AppLayout>

    <!-- ─── Edit Bank Account Dialog ──────────────────────────────── -->
    <Dialog v-model:open="editOpen">
        <DialogContent class="max-w-[95vw] sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Bank Account</DialogTitle>
                <DialogDescription>Update the details for "{{ targetAccount?.name }}".</DialogDescription>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submitEdit">
                <div class="grid gap-1.5">
                    <Label for="edit-name">Account Label</Label>
                    <Input id="edit-name" v-model="editForm.name" placeholder="e.g. My Savings Account" autocomplete="off" />
                    <InputError :message="editForm.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="edit-bank_name">Bank Name</Label>
                    <Input id="edit-bank_name" v-model="editForm.bank_name" placeholder="e.g. National Bank" autocomplete="off" />
                    <InputError :message="editForm.errors.bank_name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="edit-branch_name">Branch Name</Label>
                    <Input id="edit-branch_name" v-model="editForm.branch_name" placeholder="e.g. Downtown Branch" autocomplete="off" />
                    <InputError :message="editForm.errors.branch_name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="edit-account_number">Account Number</Label>
                    <Input id="edit-account_number" v-model="editForm.account_number" placeholder="e.g. 1234567890" autocomplete="off" />
                    <InputError :message="editForm.errors.account_number" />
                </div>

                <DialogFooter class="pt-2">
                    <Button type="button" variant="outline" @click="editOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing">
                        {{ editForm.processing ? 'Saving\u2026' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- ─── Delete Bank Account AlertDialog ──────────────────────── -->
    <AlertDialog v-model:open="deleteOpen">
        <AlertDialogContent class="max-w-[95vw] sm:max-w-md">
            <AlertDialogHeader>
                <AlertDialogTitle>Delete "{{ targetAccount?.name }}"?</AlertDialogTitle>
                <AlertDialogDescription as="div">
                    <span>This action <strong>cannot be undone</strong>. The account record will be permanently removed.</span>
                    <span
                        v-if="targetAccount && Number(targetAccount.balance) > 0"
                        class="mt-2 block rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                    >
                        &#x26A0; This account currently holds <strong>{{ formatCurrency(targetAccount.balance) }}</strong>.
                        Deleting it will remove this amount from your tracked total balance.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-red-600 text-white hover:bg-red-700 focus:ring-red-600"
                    @click="confirmDelete"
                >
                    Delete Account
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
