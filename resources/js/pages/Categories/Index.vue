<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, Pencil, Plus, Shield, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

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

type CategoryType = 'income' | 'expense' | 'transfer';

interface Category {
    id: number;
    user_id: number | null;
    name: string;
    type: CategoryType;
    is_system: boolean;
    icon: string | null;
    color: string | null;
}

// ─── Props ────────────────────────────────────────────────────────────────────

defineProps<{
    categories: Category[];
}>();

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categories', href: '/categories' },
];

// ─── Color presets ────────────────────────────────────────────────────────────

const colorPresets = [
    '#ef4444', '#f97316', '#eab308', '#22c55e',
    '#14b8a6', '#3b82f6', '#8b5cf6', '#ec4899',
    '#64748b', '#92400e', '#166534', '#1e3a8a',
];

// ─── Create form ──────────────────────────────────────────────────────────────

const createOpen = ref(false);

const createForm = useForm({
    name:  '',
    type:  'expense' as CategoryType,
    icon:  '',
    color: '#3b82f6',
});

function submitCreate() {
    createForm.post(route('categories.store'), {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });
}

// ─── Edit form ────────────────────────────────────────────────────────────────

const editOpen = ref(false);
const editTarget = ref<Category | null>(null);

const editForm = useForm({
    name:  '',
    type:  'expense' as CategoryType,
    icon:  '',
    color: '#3b82f6',
});

function openEdit(cat: Category) {
    editTarget.value = cat;
    editForm.name  = cat.name;
    editForm.type  = cat.type;
    editForm.icon  = cat.icon ?? '';
    editForm.color = cat.color ?? '#3b82f6';
    editOpen.value = true;
}

function submitEdit() {
    if (!editTarget.value) return;
    editForm.put(route('categories.update', { category: editTarget.value.id }), {
        onSuccess: () => {
            editOpen.value = false;
            editTarget.value = null;
            editForm.reset();
        },
    });
}

// Reset edit form when dialog closes
watch(editOpen, (val) => {
    if (!val) {
        editTarget.value = null;
        editForm.reset();
    }
});

// ─── Delete ───────────────────────────────────────────────────────────────────

const deleteForm = useForm({});
const deleteConfirmOpen = ref(false);
const deleteTarget = ref<Category | null>(null);

function deleteCategory(cat: Category) {
    deleteTarget.value = cat;
    deleteConfirmOpen.value = true;
}

function confirmDelete() {
    if (!deleteTarget.value) return;
    deleteForm.delete(route('categories.destroy', { category: deleteTarget.value.id }), {
        onFinish: () => {
            deleteConfirmOpen.value = false;
            deleteTarget.value = null;
        },
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

const typeBadge: Record<string, string> = {
    income:   'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    expense:  'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    transfer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
};
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">

            <!-- Page header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Categories</h1>
                    <p class="text-muted-foreground text-sm">Manage your transaction categories.</p>
                </div>

                <!-- Create dialog -->
                <Dialog v-model:open="createOpen">
                    <DialogTrigger as-child>
                        <Button size="sm" class="gap-2">
                            <Plus class="h-4 w-4" />
                            New Category
                        </Button>
                    </DialogTrigger>

                    <DialogContent class="max-w-[95vw] sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>New Category</DialogTitle>
                        </DialogHeader>

                        <form @submit.prevent="submitCreate" class="grid gap-4 py-2">
                            <!-- Name -->
                            <div class="grid gap-1.5">
                                <Label for="create-name">Name</Label>
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    placeholder="e.g. Groceries"
                                    required
                                />
                                <p v-if="createForm.errors.name" class="text-destructive text-xs">
                                    {{ createForm.errors.name }}
                                </p>
                            </div>

                            <!-- Type -->
                            <div class="grid gap-1.5">
                                <Label for="create-type">Type</Label>
                                <select
                                    id="create-type"
                                    v-model="createForm.type"
                                    class="border-input bg-background text-sm ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                    <option value="transfer">Transfer</option>
                                </select>
                                <p v-if="createForm.errors.type" class="text-destructive text-xs">
                                    {{ createForm.errors.type }}
                                </p>
                            </div>

                            <!-- Icon -->
                            <div class="grid gap-1.5">
                                <Label for="create-icon">Icon name <span class="text-muted-foreground font-normal">(optional, Lucide name e.g. "shopping-cart")</span></Label>
                                <Input
                                    id="create-icon"
                                    v-model="createForm.icon"
                                    placeholder="shopping-cart"
                                />
                            </div>

                            <!-- Color -->
                            <div class="grid gap-1.5">
                                <Label for="create-color">Color</Label>
                                <div class="flex items-center gap-3">
                                    <input
                                        id="create-color"
                                        type="color"
                                        v-model="createForm.color"
                                        class="h-10 w-10 cursor-pointer rounded border p-0.5"
                                    />
                                    <Input
                                        v-model="createForm.color"
                                        placeholder="#3b82f6"
                                        class="font-mono"
                                        maxlength="7"
                                    />
                                </div>
                                <!-- Presets -->
                                <div class="mt-1 flex flex-wrap gap-2">
                                    <button
                                        v-for="hex in colorPresets"
                                        :key="hex"
                                        type="button"
                                        :title="hex"
                                        class="h-6 w-6 rounded-full border-2 transition-transform hover:scale-110"
                                        :style="{ backgroundColor: hex, borderColor: createForm.color === hex ? '#000' : 'transparent' }"
                                        @click="createForm.color = hex"
                                    />
                                </div>
                                <p v-if="createForm.errors.color" class="text-destructive text-xs">
                                    {{ createForm.errors.color }}
                                </p>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
                                <Button type="submit" :disabled="createForm.processing">Create</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Table card -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold">
                        All Categories
                        <span class="text-muted-foreground ml-2 text-sm font-normal">
                            ({{ categories.length }} total)
                        </span>
                    </CardTitle>
                </CardHeader>

                <CardContent class="p-0">

                    <!-- Empty -->
                    <div
                        v-if="categories.length === 0"
                        class="text-muted-foreground flex flex-col items-center justify-center gap-2 py-16 text-center"
                    >
                        <p class="text-sm">No categories yet. Create one above.</p>
                    </div>

                    <!-- Mobile Cards -->
                    <template v-else>
                        <div class="divide-y md:hidden">
                            <div
                                v-for="cat in categories"
                                :key="'m-' + cat.id"
                                class="flex items-center gap-3 px-4 py-3"
                            >
                                <!-- Color dot -->
                                <span
                                    class="inline-block h-4 w-4 shrink-0 rounded-full border"
                                    :style="cat.color ? { backgroundColor: cat.color } : {}"
                                    :class="!cat.color ? 'bg-muted' : ''"
                                />

                                <!-- Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-sm font-medium">{{ cat.name }}</span>
                                        <span
                                            v-if="cat.is_system"
                                            class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"
                                        >
                                            <Shield class="h-2.5 w-2.5" />
                                            system
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                                            :class="typeBadge[cat.type]"
                                        >
                                            {{ cat.type }}
                                        </span>
                                        <span v-if="cat.icon" class="font-mono text-xs text-muted-foreground">{{ cat.icon }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div v-if="!cat.is_system" class="flex shrink-0 items-center gap-1">
                                    <Button size="icon" variant="ghost" class="h-8 w-8" title="Edit" @click="openEdit(cat)">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="h-8 w-8 text-destructive hover:text-destructive"
                                        title="Delete"
                                        :disabled="deleteForm.processing"
                                        @click="deleteCategory(cat)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden overflow-x-auto md:block">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/40 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        <th class="px-4 py-3">Color</th>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Type</th>
                                        <th class="hidden px-4 py-3 lg:table-cell">Icon</th>
                                        <th class="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        class="transition-colors hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-block h-4 w-4 rounded-full border"
                                                :style="cat.color ? { backgroundColor: cat.color } : {}"
                                                :class="!cat.color ? 'bg-muted' : ''"
                                            />
                                        </td>
                                        <td class="px-4 py-3 font-medium">
                                            <span class="flex items-center gap-2">
                                                {{ cat.name }}
                                                <span
                                                    v-if="cat.is_system"
                                                    class="inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"
                                                >
                                                    <Shield class="h-2.5 w-2.5" />
                                                    system
                                                </span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                                :class="typeBadge[cat.type]"
                                            >
                                                {{ cat.type }}
                                            </span>
                                        </td>
                                        <td class="hidden px-4 py-3 font-mono text-xs text-muted-foreground lg:table-cell">
                                            {{ cat.icon ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <Button
                                                    v-if="!cat.is_system"
                                                    size="icon"
                                                    variant="ghost"
                                                    class="h-8 w-8"
                                                    title="Edit"
                                                    @click="openEdit(cat)"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    v-if="!cat.is_system"
                                                    size="icon"
                                                    variant="ghost"
                                                    class="h-8 w-8 text-destructive hover:text-destructive"
                                                    title="Delete"
                                                    :disabled="deleteForm.processing"
                                                    @click="deleteCategory(cat)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </Button>
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

    <!-- Delete confirmation dialog -->
    <Dialog v-model:open="deleteConfirmOpen">
        <DialogContent class="max-w-[95vw] sm:max-w-sm">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-red-600">
                    <AlertTriangle class="h-5 w-5" />
                    Delete Category
                </DialogTitle>
            </DialogHeader>

            <div class="py-2">
                <p class="text-sm text-muted-foreground">
                    Are you sure you want to delete
                    <span class="font-semibold text-foreground">"{{ deleteTarget?.name }}"</span>?
                </p>
                <p class="mt-1 text-xs text-red-500">This action cannot be undone.</p>
            </div>

            <DialogFooter class="gap-2">
                <Button
                    type="button"
                    variant="outline"
                    @click="deleteConfirmOpen = false"
                    :disabled="deleteForm.processing"
                >
                    No, Cancel
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    :disabled="deleteForm.processing"
                    @click="confirmDelete"
                >
                    Yes, Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Edit dialog (outside card to avoid nesting issues) -->
    <Dialog v-model:open="editOpen">
        <DialogContent class="max-w-[95vw] sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Category</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submitEdit" class="grid gap-4 py-2">
                <!-- Name -->
                <div class="grid gap-1.5">
                    <Label for="edit-name">Name</Label>
                    <Input id="edit-name" v-model="editForm.name" required />
                    <p v-if="editForm.errors.name" class="text-destructive text-xs">{{ editForm.errors.name }}</p>
                </div>

                <!-- Type -->
                <div class="grid gap-1.5">
                    <Label for="edit-type">Type</Label>
                    <select
                        id="edit-type"
                        v-model="editForm.type"
                        class="border-input bg-background text-sm ring-offset-background focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                        <option value="transfer">Transfer</option>
                    </select>
                    <p v-if="editForm.errors.type" class="text-destructive text-xs">{{ editForm.errors.type }}</p>
                </div>

                <!-- Icon -->
                <div class="grid gap-1.5">
                    <Label for="edit-icon">Icon name <span class="text-muted-foreground font-normal">(optional)</span></Label>
                    <Input id="edit-icon" v-model="editForm.icon" placeholder="shopping-cart" />
                </div>

                <!-- Color -->
                <div class="grid gap-1.5">
                    <Label for="edit-color">Color</Label>
                    <div class="flex items-center gap-3">
                        <input
                            id="edit-color"
                            type="color"
                            v-model="editForm.color"
                            class="h-10 w-10 cursor-pointer rounded border p-0.5"
                        />
                        <Input v-model="editForm.color" placeholder="#3b82f6" class="font-mono" maxlength="7" />
                    </div>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <button
                            v-for="hex in colorPresets"
                            :key="hex"
                            type="button"
                            :title="hex"
                            class="h-6 w-6 rounded-full border-2 transition-transform hover:scale-110"
                            :style="{ backgroundColor: hex, borderColor: editForm.color === hex ? '#000' : 'transparent' }"
                            @click="editForm.color = hex"
                        />
                    </div>
                    <p v-if="editForm.errors.color" class="text-destructive text-xs">{{ editForm.errors.color }}</p>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="editOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing">Save Changes</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
