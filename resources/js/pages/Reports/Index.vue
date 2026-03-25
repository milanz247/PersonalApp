<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { FileDown, FileSpreadsheet } from 'lucide-vue-next';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports', href: '/reports' },
];

// ─── Monthly PDF ──────────────────────────────────────────────────────────────

const now = new Date();
const pdfMonth = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`);
const pdfLoading = ref(false);

function downloadPdf() {
    pdfLoading.value = true;
    // Direct download — not an Inertia visit
    window.location.href = route('reports.pdf', { month: pdfMonth.value });
    // Release loading after a short delay (download starts in background)
    setTimeout(() => { pdfLoading.value = false; }, 3000);
}

// ─── CSV Export ───────────────────────────────────────────────────────────────

const csvStartDate = ref(new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]);
const csvEndDate = ref(now.toISOString().split('T')[0]);
const csvLoading = ref(false);

function downloadCsv() {
    if (csvStartDate.value > csvEndDate.value) return;
    csvLoading.value = true;
    window.location.href = route('reports.csv', {
        start_date: csvStartDate.value,
        end_date: csvEndDate.value,
    });
    setTimeout(() => { csvLoading.value = false; }, 3000);
}

// ─── Display helpers ──────────────────────────────────────────────────────────

function displayMonth(monthStr: string): string {
    const [year, month] = monthStr.split('-');
    const date = new Date(Number(year), Number(month) - 1);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Reports</h1>
                <p class="text-sm text-muted-foreground">Generate financial reports and export your transaction data.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Monthly PDF Report -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <FileDown class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <CardTitle class="text-base">Monthly Report (PDF)</CardTitle>
                                <CardDescription>Download a complete summary of your monthly finances.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4">
                            <div class="grid gap-1.5">
                                <Label for="pdf-month">Select Month</Label>
                                <Input id="pdf-month" v-model="pdfMonth" type="month" />
                            </div>

                            <div class="rounded-md bg-muted/50 px-3 py-2 text-sm text-muted-foreground">
                                Report for <strong class="text-foreground">{{ displayMonth(pdfMonth) }}</strong> will include:
                                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                                    <li>Income & expense summary</li>
                                    <li>Savings rate</li>
                                    <li>Category-wise expense breakdown</li>
                                    <li>Account balances snapshot</li>
                                </ul>
                            </div>

                            <Button @click="downloadPdf" :disabled="pdfLoading" class="w-full">
                                <FileDown class="mr-2 h-4 w-4" />
                                {{ pdfLoading ? 'Generating…' : 'Download Monthly Report' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- CSV Export -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <FileSpreadsheet class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <CardTitle class="text-base">Export Transactions (CSV)</CardTitle>
                                <CardDescription>Export raw transaction data for spreadsheets or analysis.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <Label for="csv-start">Start Date</Label>
                                    <Input id="csv-start" v-model="csvStartDate" type="date" />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="csv-end">End Date</Label>
                                    <Input id="csv-end" v-model="csvEndDate" type="date" />
                                </div>
                            </div>

                            <div v-if="csvStartDate > csvEndDate" class="text-xs text-destructive">
                                End date must be after start date.
                            </div>

                            <div class="rounded-md bg-muted/50 px-3 py-2 text-sm text-muted-foreground">
                                CSV includes: Date, Type, Category, Amount, Fee, Accounts, Notes.
                            </div>

                            <Button
                                @click="downloadCsv"
                                :disabled="csvLoading || csvStartDate > csvEndDate"
                                variant="outline"
                                class="w-full"
                            >
                                <FileSpreadsheet class="mr-2 h-4 w-4" />
                                {{ csvLoading ? 'Exporting…' : 'Export Transactions' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
