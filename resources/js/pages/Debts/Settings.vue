<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Settings, Save, Info } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    debtAutoSendInitial: boolean;
    debtReminderDaysBefore: number;
    debtInitialMessage: string;
    debtReminderMessage: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Debts & Loans', href: '/debts' },
    { title: 'Notification Settings', href: '/debts/settings' },
];

const form = useForm({
    debt_auto_send_initial: props.debtAutoSendInitial,
    debt_reminder_days_before: props.debtReminderDaysBefore,
    debt_initial_message: props.debtInitialMessage,
    debt_reminder_message: props.debtReminderMessage,
});

const saved = ref(false);

function submit() {
    form.post(route('debts.settings.save'), {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => saved.value = false, 3000);
        },
    });
}

const placeholders = [
    { tag: '{person_name}', desc: 'Name of the borrower' },
    { tag: '{amount}', desc: 'Original loan amount' },
    { tag: '{remaining_amount}', desc: 'Current outstanding amount' },
    { tag: '{date}', desc: 'Date the loan was created' },
    { tag: '{due_date}', desc: 'Due date (or "N/A")' },
    { tag: '{due_date_line}', desc: 'Full due-date sentence (or empty)' },
    { tag: '{lender_name}', desc: 'Your name' },
];
</script>

<template>
    <Head title="Debt Notification Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Notification Settings</h1>
                    <p class="text-muted-foreground text-sm">Configure how debt reminders and notifications are sent.</p>
                </div>
                <Button variant="outline" size="sm" as="a" href="/debts" class="gap-1.5">
                    ← Back to Debts
                </Button>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6 max-w-3xl">

                <!-- Auto-send initial notification -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Settings class="h-4 w-4" />
                            General Settings
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <Label class="text-sm font-medium">Auto-send notification when lending</Label>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    Automatically notify the borrower via email when you record a new loan.
                                </p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="form.debt_auto_send_initial"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                :class="form.debt_auto_send_initial ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                @click="form.debt_auto_send_initial = !form.debt_auto_send_initial"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="form.debt_auto_send_initial ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="days-before">Reminder days before due date</Label>
                            <Input
                                id="days-before"
                                type="number"
                                min="1"
                                max="30"
                                v-model.number="form.debt_reminder_days_before"
                                class="max-w-[120px]"
                            />
                            <p class="text-xs text-muted-foreground">
                                Send an automatic reminder this many days before the due date. Overdue debts get daily reminders.
                            </p>
                            <p v-if="form.errors.debt_reminder_days_before" class="text-destructive text-xs">
                                {{ form.errors.debt_reminder_days_before }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Placeholders reference -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Info class="h-4 w-4" />
                            Available Placeholders
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                            <div
                                v-for="p in placeholders"
                                :key="p.tag"
                                class="flex items-center gap-2 rounded-md bg-muted/50 px-3 py-1.5 text-xs"
                            >
                                <code class="rounded bg-muted px-1.5 py-0.5 font-mono text-[11px] font-semibold text-blue-600 dark:text-blue-400">
                                    {{ p.tag }}
                                </code>
                                <span class="text-muted-foreground">{{ p.desc }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Initial Message Template -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Initial Notification Message</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-2">
                        <p class="text-xs text-muted-foreground">
                            Sent when you first record a loan (via email and/or WhatsApp).
                        </p>
                        <textarea
                            v-model="form.debt_initial_message"
                            rows="6"
                            class="border-input bg-background text-sm ring-offset-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            placeholder="Hi {person_name}, ..."
                        />
                        <p v-if="form.errors.debt_initial_message" class="text-destructive text-xs">
                            {{ form.errors.debt_initial_message }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Reminder Message Template -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Reminder Message</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-2">
                        <p class="text-xs text-muted-foreground">
                            Sent automatically before the due date and for overdue debts.
                        </p>
                        <textarea
                            v-model="form.debt_reminder_message"
                            rows="6"
                            class="border-input bg-background text-sm ring-offset-background focus-visible:ring-ring w-full rounded-md border px-3 py-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            placeholder="Hi {person_name}, ..."
                        />
                        <p v-if="form.errors.debt_reminder_message" class="text-destructive text-xs">
                            {{ form.errors.debt_reminder_message }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Save -->
                <div class="flex items-center gap-3">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        class="gap-2"
                    >
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : 'Save Settings' }}
                    </Button>
                    <Transition
                        enter-active-class="transition duration-300"
                        enter-from-class="opacity-0 translate-y-1"
                        leave-active-class="transition duration-200"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="saved" class="text-sm font-medium text-green-600 dark:text-green-400">
                            ✓ Settings saved
                        </span>
                    </Transition>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
