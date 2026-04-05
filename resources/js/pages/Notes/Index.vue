<script setup lang="ts">
import NotebookLayout from '@/layouts/NotebookLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Plus, Search, X, BookOpen, Flame, Calendar, TrendingUp,
    CalendarDays, Pencil, Trash2,
} from 'lucide-vue-next';

// ── Types ─────────────────────────────────────────────────────────
interface Note {
    id: number;
    title: string;
    content: string | null;
    color: string;
    is_pinned: boolean;
    note_date: string;
    mood: string | null;
}
interface Stats {
    totalEntries: number;
    thisMonthEntries: number;
    bestMonth: string;
    streak: number;
}

// ── Props ─────────────────────────────────────────────────────────
const props = defineProps<{
    stats: Stats;
    recentEntries: Note[];
    onThisDay: Note[];
    todayStr: string;
}>();

// ── Helpers ───────────────────────────────────────────────────────
const todayStr = props.todayStr;

const moodEmoji: Record<string, string> = {
    happy: '😊', sad: '😢', angry: '😠', calm: '😌', excited: '🤩',
};
const colorBg: Record<string, string> = {
    default: '',
    yellow:  'bg-yellow-50 dark:bg-yellow-950/30',
    green:   'bg-green-50 dark:bg-green-950/30',
    blue:    'bg-blue-50 dark:bg-blue-950/30',
    pink:    'bg-pink-50 dark:bg-pink-950/30',
    purple:  'bg-purple-50 dark:bg-purple-950/30',
};
const moodOptions = [
    { value: 'happy',   emoji: '😊', label: 'Happy' },
    { value: 'excited', emoji: '🤩', label: 'Excited' },
    { value: 'calm',    emoji: '😌', label: 'Calm' },
    { value: 'sad',     emoji: '😢', label: 'Sad' },
    { value: 'angry',   emoji: '😠', label: 'Angry' },
];
const colorOptions = [
    { value: 'default', bg: 'bg-zinc-200' },
    { value: 'yellow',  bg: 'bg-yellow-300' },
    { value: 'green',   bg: 'bg-green-300' },
    { value: 'blue',    bg: 'bg-blue-300' },
    { value: 'pink',    bg: 'bg-pink-300' },
    { value: 'purple',  bg: 'bg-purple-300' },
];

function formatDate(raw: string): string {
    return new Date(raw).toLocaleDateString('en-US', {
        weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
    });
}

// ── Create ────────────────────────────────────────────────────────
function openCreate() {
    router.get('/notes/new');
}

// ── Search ────────────────────────────────────────────────────────
const showSearch   = ref(false);
const searchQuery  = ref('');
const searchResults= ref<{ date: string; notes: Note[] }[]>([]);
let   searchTimer: ReturnType<typeof setTimeout> | null = null;

function onSearchInput() {
    if (searchTimer) clearTimeout(searchTimer);
    if (!searchQuery.value.trim()) { searchResults.value = []; return; }
    searchTimer = setTimeout(async () => {
        try {
            const res = await fetch(`/notes/search?q=${encodeURIComponent(searchQuery.value)}`);
            const data = await res.json();
            searchResults.value = data.results ?? [];
        } catch {}
    }, 300);
}
function goToDay(dateStr: string) {
    showSearch.value = false;
    const y = parseInt(dateStr.split('-')[0]);
    router.get('/notes/planner', { year: y, day: dateStr });
}

// ── Edit ──────────────────────────────────────────────────────────
const showEdit   = ref(false);
const editingId  = ref<number | null>(null);
const editForm   = useForm({
    title: '', content: '', color: 'default', note_date: '', mood: '' as string | null,
});
function openEdit(note: Note) {
    editingId.value    = note.id;
    editForm.title     = note.title;
    editForm.content   = note.content ?? '';
    editForm.color     = note.color;
    editForm.note_date = note.note_date;
    editForm.mood      = note.mood;
    showEdit.value     = true;
}
function submitEdit() {
    if (!editingId.value) return;
    editForm.put(route('notes.update', editingId.value), {
        preserveScroll: true,
        onSuccess: () => { showEdit.value = false; },
    });
}

// ── Delete ────────────────────────────────────────────────────────
const showDelete    = ref(false);
const deletingId    = ref<number | null>(null);
const deletingTitle = ref('');
function openDelete(note: Note) {
    deletingId.value    = note.id;
    deletingTitle.value = note.title;
    showDelete.value    = true;
}
function confirmDelete() {
    if (!deletingId.value) return;
    router.delete(route('notes.destroy', deletingId.value), {
        preserveScroll: true,
        onSuccess: () => { showDelete.value = false; },
    });
}
</script>

<template>
    <Head title="My Diary" />
    <NotebookLayout>
        <div class="mx-auto max-w-5xl px-3 py-6 sm:px-4 sm:py-8">

            <!-- Header -->
            <div class="mb-6 flex flex-col gap-3 sm:mb-8 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="font-serif text-2xl italic text-zinc-800 dark:text-zinc-100 sm:text-3xl">My Diary</h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 sm:text-sm">
                        {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-1.5" @click="showSearch = true">
                        <Search class="h-3.5 w-3.5" />
                        Search
                    </Button>
                    <Button size="sm" class="gap-1.5 bg-violet-600 hover:bg-violet-700" @click="openCreate()">
                        <Plus class="h-3.5 w-3.5" />
                        New Entry
                    </Button>
                </div>
            </div>

            <!-- Stats row -->
            <div class="mb-6 grid grid-cols-2 gap-2 sm:mb-8 sm:grid-cols-4 sm:gap-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <BookOpen class="h-4 w-4" />
                        <span class="text-xs font-medium uppercase tracking-wider">Total</span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ stats.totalEntries }}</div>
                    <div class="text-xs text-zinc-400">entries</div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <Calendar class="h-4 w-4" />
                        <span class="text-xs font-medium uppercase tracking-wider">This Month</span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ stats.thisMonthEntries }}</div>
                    <div class="text-xs text-zinc-400">entries</div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <Flame class="h-4 w-4 text-orange-400" />
                        <span class="text-xs font-medium uppercase tracking-wider">Streak</span>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ stats.streak }}</div>
                    <div class="text-xs text-zinc-400">days</div>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <TrendingUp class="h-4 w-4 text-green-500" />
                        <span class="text-xs font-medium uppercase tracking-wider">Best Month</span>
                    </div>
                    <div class="mt-2 text-sm font-bold leading-tight text-zinc-800 dark:text-zinc-100">{{ stats.bestMonth }}</div>
                </div>
            </div>

            <!-- Recent Entries -->
            <div v-if="recentEntries.length > 0" class="mb-6 sm:mb-8">
                <div class="mb-3 flex items-center gap-2">
                    <BookOpen class="h-4 w-4 text-violet-500" />
                    <h2 class="font-semibold text-zinc-700 dark:text-zinc-300">Recent Entries</h2>
                </div>
                <div class="flex flex-col gap-3">
                    <div
                        v-for="note in recentEntries"
                        :key="note.id"
                        class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 transition-colors"
                        :class="colorBg[note.color] ?? ''"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1 cursor-pointer" @click="goToDay(note.note_date)">
                                <div class="flex items-center gap-2">
                                    <span v-if="note.mood" class="text-sm">{{ moodEmoji[note.mood] }}</span>
                                    <h3 class="truncate font-semibold text-zinc-800 dark:text-zinc-100">{{ note.title }}</h3>
                                </div>
                                <div class="mt-0.5 text-xs text-zinc-400">{{ formatDate(note.note_date) }}</div>
                            </div>
                            <div v-if="note.note_date === todayStr" class="flex shrink-0 items-center gap-1">
                                <button
                                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-950/30"
                                    title="Edit entry"
                                    @click="openEdit(note)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                                    title="Delete entry"
                                    @click="openDelete(note)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                        <p v-if="note.content"
                           class="mt-1.5 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400"
                           v-html="note.content?.replace(/<[^>]+>/g, ' ').slice(0, 150)" />
                    </div>
                </div>
            </div>

            <!-- On This Day (full width) -->
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-5 dark:border-violet-900/50 dark:bg-violet-950/20">
                <div class="mb-4 flex items-center gap-2">
                    <CalendarDays class="h-5 w-5 text-violet-500" />
                    <h2 class="font-semibold text-violet-700 dark:text-violet-300">On This Day</h2>
                    <span class="ml-auto rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                        1 year ago
                    </span>
                </div>

                <div v-if="onThisDay.length === 0"
                     class="py-8 text-center font-serif italic text-violet-400 dark:text-violet-600">
                    Nothing written on this day last year.
                </div>

                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="note in onThisDay"
                        :key="note.id"
                        class="rounded-lg bg-white px-4 py-3 shadow-sm dark:bg-zinc-900 cursor-pointer hover:bg-violet-50 dark:hover:bg-violet-950/30 transition-colors"
                        @click="goToDay(note.note_date)"
                    >
                        <div class="flex items-center gap-2">
                            <span v-if="note.mood" class="text-lg">{{ moodEmoji[note.mood] }}</span>
                            <span class="truncate font-medium text-zinc-800 dark:text-zinc-200">{{ note.title }}</span>
                        </div>
                        <p v-if="note.content"
                           class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400"
                           v-html="note.content?.replace(/<[^>]+>/g, ' ').slice(0, 120)" />
                    </div>
                </div>
            </div>
        </div>
    </NotebookLayout>

    <!-- ─ Search Dialog ─────────────────────────────────── -->
    <Dialog v-model:open="showSearch">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Search Diary</DialogTitle>
            </DialogHeader>
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <Input
                    v-model="searchQuery"
                    placeholder="Search entries…"
                    class="pl-9"
                    @input="onSearchInput"
                    autofocus
                />
                <button v-if="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600"
                    @click="searchQuery = ''; searchResults = []">
                    <X class="h-4 w-4" />
                </button>
            </div>
            <div class="max-h-72 overflow-y-auto">
                <div v-if="!searchQuery.trim()" class="py-8 text-center text-sm text-zinc-400">
                    Start typing to search…
                </div>
                <div v-else-if="searchResults.length === 0" class="py-8 text-center text-sm text-zinc-400">
                    No entries found.
                </div>
                <div v-else v-for="group in searchResults" :key="group.date" class="mb-3">
                    <div class="mb-1 px-1 text-xs font-semibold text-zinc-400">{{ formatDate(group.date) }}</div>
                    <button
                        v-for="note in group.notes"
                        :key="note.id"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-violet-50 dark:hover:bg-violet-950/30"
                        @click="goToDay(group.date)"
                    >
                        <div class="font-medium text-zinc-800 dark:text-zinc-200">{{ note.title }}</div>
                        <div v-if="note.content"
                             class="line-clamp-1 text-xs text-zinc-400"
                             v-html="note.content?.replace(/<[^>]+>/g, ' ').slice(0, 80)" />
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
    <!-- ─ Edit Dialog ───────────────────────────────────────── -->
    <Dialog v-model:open="showEdit">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="font-serif italic text-zinc-700 dark:text-zinc-300">Edit Entry</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitEdit" class="flex flex-col gap-4">
                <div>
                    <Label for="edit-title">Title</Label>
                    <Input id="edit-title" v-model="editForm.title" class="mt-1" required />
                    <p v-if="editForm.errors.title" class="mt-1 text-xs text-red-500">{{ editForm.errors.title }}</p>
                </div>
                <!-- Mood -->
                <div>
                    <Label class="mb-1.5 block">Mood</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="m in moodOptions"
                            :key="m.value"
                            type="button"
                            class="rounded-xl px-3 py-1.5 text-sm transition-all"
                            :class="editForm.mood === m.value
                                ? 'bg-violet-600 text-white shadow'
                                : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300'"
                            @click="editForm.mood = editForm.mood === m.value ? null : m.value"
                        >{{ m.emoji }} {{ m.label }}</button>
                    </div>
                </div>
                <!-- Color -->
                <div>
                    <Label class="mb-1.5 block">Color</Label>
                    <div class="flex gap-2">
                        <button
                            v-for="c in colorOptions"
                            :key="c.value"
                            type="button"
                            class="h-6 w-6 rounded-full border-2 transition-all"
                            :class="[c.bg, editForm.color === c.value ? 'border-violet-500 scale-110' : 'border-transparent']"
                            @click="editForm.color = c.value"
                        />
                    </div>
                </div>
                <!-- Content -->
                <div>
                    <Label class="mb-1.5 block">Content</Label>
                    <TiptapEditor v-model="editForm.content" />
                    <p v-if="editForm.errors.content" class="mt-1 text-xs text-red-500">{{ editForm.errors.content }}</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="showEdit = false">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing" class="bg-violet-600 hover:bg-violet-700">
                        {{ editForm.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <!-- ─ Delete Confirmation ───────────────────────────────── -->
    <AlertDialog v-model:open="showDelete">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Delete Entry?</AlertDialogTitle>
                <AlertDialogDescription>
                    Are you sure you want to delete "<strong>{{ deletingTitle }}</strong>"? This cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction class="bg-red-600 hover:bg-red-700" @click="confirmDelete">Delete</AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<style scoped>
.note-list-enter-active,
.note-list-leave-active { transition: all 0.3s ease; }
.note-list-enter-from   { opacity: 0; transform: translateY(12px); }
.note-list-leave-to     { opacity: 0; transform: translateX(-12px); }
</style>
