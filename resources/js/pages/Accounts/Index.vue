<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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
import { ArrowLeftRight, Building2, CreditCard, PlusCircle, TrendingUp, Wallet } from 'lucide-vue-next';

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
</script>

<template>
    <Head title="Accounts" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Accounts</h1>
                    <p class="text-muted-foreground text-sm">Manage your wallet and bank accounts.</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Income Dialog -->
                    <Dialog v-model:open="incomeOpen">
                        <DialogTrigger as-child>
                            <Button class="gap-1.5 bg-green-600 text-white hover:bg-green-700" size="sm">
                                <TrendingUp class="h-4 w-4" />
                                + Income
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
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
                            <Button variant="outline">
                                <ArrowLeftRight class="mr-2 h-4 w-4" />
                                Transfer
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="sm:max-w-md">
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
                            <Button>
                                <PlusCircle class="mr-2 h-4 w-4" />
                                Add Bank Account
                            </Button>
                        </DialogTrigger>

                    <DialogContent class="sm:max-w-md">
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
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-muted-foreground">
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
            <div>
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-widest text-muted-foreground">
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
                        class="transition-shadow hover:shadow-md"
                    >
                        <CardHeader class="flex flex-row items-start justify-between pb-2">
                            <div class="space-y-0.5">
                                <CardTitle class="text-base">{{ account.name }}</CardTitle>
                                <CardDescription>{{ account.bank_name }}</CardDescription>
                            </div>
                            <CreditCard class="h-5 w-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent class="space-y-1">
                            <p class="text-2xl font-bold">
                                {{ formatCurrency(account.balance) }}
                            </p>
                            <div class="space-y-0.5 pt-2 text-xs text-muted-foreground">
                                <p>
                                    <span class="font-medium">Branch:</span>
                                    {{ account.branch_name ?? '—' }}
                                </p>
                                <p>
                                    <span class="font-medium">Account No:</span>
                                    {{ account.account_number ?? '—' }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
