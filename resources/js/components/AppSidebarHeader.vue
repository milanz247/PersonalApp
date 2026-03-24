<script setup lang="ts">
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { LogOut, MinusCircle } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
}>();

const page = usePage<SharedData>();

// ─── Live clock ───────────────────────────────────────────────────────────────

function formatCurrentDate(format: string): string {
    const now = new Date();
    const d   = String(now.getDate()).padStart(2, '0');
    const mo  = String(now.getMonth() + 1).padStart(2, '0');
    const y   = String(now.getFullYear());
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    switch (format) {
        case 'DD/MM/YYYY':   return `${d}/${mo}/${y}`;
        case 'MM/DD/YYYY':   return `${mo}/${d}/${y}`;
        case 'YYYY-MM-DD':   return `${y}-${mo}-${d}`;
        case 'DD-MM-YYYY':   return `${d}-${mo}-${y}`;
        case 'YYYY/MM/DD':   return `${y}/${mo}/${d}`;
        case 'MMM DD, YYYY': return `${months[now.getMonth()]} ${d}, ${y}`;
        default:             return `${d}/${mo}/${y}`;
    }
}

const dateFormat  = page.props.userSettings?.date_format ?? 'DD/MM/YYYY';
const currentDate = ref(formatCurrentDate(dateFormat));
const currentTime = ref(new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));

let clockTimer: ReturnType<typeof setInterval>;

onMounted(() => {
    clockTimer = setInterval(() => {
        currentDate.value = formatCurrentDate(dateFormat);
        currentTime.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }, 1000);
});

onUnmounted(() => clearInterval(clockTimer));

// ─── Expense form ─────────────────────────────────────────────────────────────

const expenseOpen = ref(false);

const expenseForm = useForm({
    type: 'expense',
    amount: '',
    category_id: '',
    from_account_id: '',
    date: new Date().toISOString().slice(0, 10),
    note: '',
});

function submitExpense() {
    expenseForm.post(route('transactions.store'), {
        onSuccess: () => {
            expenseOpen.value = false;
            expenseForm.reset();
            expenseForm.date = new Date().toISOString().slice(0, 10);
        },
    });
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- Left: sidebar trigger + breadcrumbs -->
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === breadcrumbs.length - 1">
                                    <BreadcrumbPage>{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href">{{ item.title }}</BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <!-- Right: clock + quick actions -->
        <div class="flex items-center gap-4">

            <!-- Live date/time (hidden on mobile) -->
            <div class="hidden flex-col items-end gap-0.5 md:flex">
                <span class="text-muted-foreground text-xs">{{ currentDate }}</span>
                <span class="font-mono text-sm tabular-nums leading-none">{{ currentTime }}</span>
            </div>

            <!-- Logout icon (no label) -->
            <Link
                method="post"
                :href="route('logout')"
                as="button"
                class="text-muted-foreground hover:text-foreground inline-flex h-8 w-8 items-center justify-center rounded-md transition-colors hover:bg-muted"
                title="Sign out"
            >
                <LogOut class="h-4 w-4" />
            </Link>

        <Dialog v-model:open="expenseOpen">
            <DialogTrigger as-child>
                <Button variant="destructive" size="sm" class="gap-1.5">
                    <MinusCircle class="h-4 w-4" />
                    + Expense
                </Button>
            </DialogTrigger>

            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Expense</DialogTitle>
                    <DialogDescription>Record a new expense from one of your accounts.</DialogDescription>
                </DialogHeader>

                <!-- Empty-state warning -->
                <div
                    v-if="page.props.expenseCategories.length === 0 || page.props.userAccounts.length === 0"
                    class="rounded-md bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                >
                    <span v-if="page.props.expenseCategories.length === 0">
                        No expense categories yet.
                        <a href="/categories" class="underline">Create one first.</a>
                    </span>
                    <span v-else>No accounts found. Please create an account first.</span>
                </div>

                <form v-else class="grid gap-4 py-2" @submit.prevent="submitExpense">
                    <!-- Amount -->
                    <div class="grid gap-1.5">
                        <Label for="exp-amount">Amount</Label>
                        <Input
                            id="exp-amount"
                            v-model="expenseForm.amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            placeholder="0.00"
                            required
                        />
                        <p v-if="expenseForm.errors.amount" class="text-xs text-destructive">{{ expenseForm.errors.amount }}</p>
                    </div>

                    <!-- Category -->
                    <div class="grid gap-1.5">
                        <Label for="exp-category">Category</Label>
                        <select
                            id="exp-category"
                            v-model="expenseForm.category_id"
                            class="border-input bg-background ring-offset-background focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                            required
                        >
                            <option value="" disabled>Select a category</option>
                            <option v-for="cat in page.props.expenseCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <p v-if="expenseForm.errors.category_id" class="text-xs text-destructive">{{ expenseForm.errors.category_id }}</p>
                    </div>

                    <!-- From Account -->
                    <div class="grid gap-1.5">
                        <Label for="exp-account">From Account</Label>
                        <select
                            id="exp-account"
                            v-model="expenseForm.from_account_id"
                            class="border-input bg-background ring-offset-background focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                            required
                        >
                            <option value="" disabled>Select account</option>
                            <option v-for="acc in page.props.userAccounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                        <p v-if="expenseForm.errors.from_account_id" class="text-xs text-destructive">{{ expenseForm.errors.from_account_id }}</p>
                    </div>

                    <!-- Date -->
                    <div class="grid gap-1.5">
                        <Label for="exp-date">Date</Label>
                        <Input id="exp-date" v-model="expenseForm.date" type="date" required />
                        <p v-if="expenseForm.errors.date" class="text-xs text-destructive">{{ expenseForm.errors.date }}</p>
                    </div>

                    <!-- Description -->
                    <div class="grid gap-1.5">
                        <Label for="exp-note">
                            Description
                            <span class="font-normal text-muted-foreground">(optional)</span>
                        </Label>
                        <Input id="exp-note" v-model="expenseForm.note" placeholder="e.g. Grocery shopping" />
                        <p v-if="expenseForm.errors.note" class="text-xs text-destructive">{{ expenseForm.errors.note }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="expenseOpen = false">Cancel</Button>
                        <Button type="submit" variant="destructive" :disabled="expenseForm.processing">
                            {{ expenseForm.processing ? 'Saving…' : 'Add Expense' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        </div>
    </header>
</template>
