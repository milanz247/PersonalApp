<script setup lang="ts">
import NotebookLayout from '@/layouts/NotebookLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { ChevronLeft, ChevronRight, Clock, BookOpen, Pencil, Trash2, Plus } from 'lucide-vue-next';

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

// ── Props ─────────────────────────────────────────────────────────
const props = defineProps<{
    year: number;
    yearCounts: Record<string, number>;
    todayStr: string;
    selectedDay?: string;
}>();

// ── Helpers ───────────────────────────────────────────────────────
const MONTH_NAMES = ['January','February','March','April','May','June',
                     'July','August','September','October','November','December'];

const todayStr = props.todayStr; // server-provided, timezone-aware

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

function readingTime(content: string | null | undefined): string {
    if (!content) return '< 1 min';
    const words = content.replace(/<[^>]+>/g, ' ').trim().split(/\s+/).length;
    const m = Math.ceil(words / 200);
    return m < 1 ? '< 1 min' : `${m} min`;
}

function pad(n: number): string { return String(n).padStart(2, '0'); }
function dateKey(y: number, m: number, d: number): string {
    return `${y}-${pad(m)}-${pad(d)}`;
}
function heatColor(count: number): string {
    if (count === 0) return '';
    if (count === 1) return 'bg-violet-200 dark:bg-violet-900/40';
    if (count === 2) return 'bg-violet-400 dark:bg-violet-700/70';
    return 'bg-violet-600 dark:bg-violet-500 text-white';
}
function daysInMonth(y: number, m: number): number {
    return new Date(y, m, 0).getDate();
}
function firstDayOfMonth(y: number, m: number): number {
    const d = new Date(y, m - 1, 1).getDay();
    return (d + 6) % 7;
}
function formatDateFull(raw: string): string {
    const [y, m, d] = raw.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
}

// ── Year navigation ───────────────────────────────────────────────
function navYear(delta: number) {
    router.get('/notes/planner', { year: props.year + delta }, { preserveState: false });
}

// ── Day Detail Dialog ─────────────────────────────────────────────
const showDayDialog = ref(false);
const selectedDate  = ref(todayStr);
const dayNotes      = ref<Note[]>([]);
const loadingDay    = ref(false);

async function selectDay(dateStr: string) {
    if (dateStr > todayStr) return;
    selectedDate.value  = dateStr;
    showDayDialog.value = true;
    loadingDay.value    = true;
    try {
        const res  = await fetch(`/notes/day-notes?date=${encodeURIComponent(dateStr)}`);
        const data = await res.json();
        dayNotes.value = data.notes ?? [];
    } catch {
        dayNotes.value = [];
    } finally {
        loadingDay.value = false;
    }
}

// ── Auto-open day dialog from URL param ───────────────────────────
onMounted(() => {
    if (props.selectedDay) {
        selectDay(props.selectedDay);
    }
});

// ── Mood / Color options ──────────────────────────────────────────
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

// ── Edit dialog ───────────────────────────────────────────────────
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
        onSuccess: () => {
            showEdit.value = false;
            selectDay(selectedDate.value);
        },
    });
}

// ── Delete dialog ─────────────────────────────────────────────────
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
        onSuccess: () => {
            showDelete.value = false;
            selectDay(selectedDate.value);
        },
    });
}
</script>

<template>
    <Head title="Planner · My Diary" />
    <NotebookLayout>
        <div class="mx-auto max-w-5xl px-3 py-6 sm:px-4 sm:py-8">

            <!-- Header -->
            <div class="mb-5 flex flex-col gap-3 sm:mb-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="font-serif text-2xl italic text-zinc-800 dark:text-zinc-100 sm:text-3xl">My Planner</h1>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 sm:text-sm">
                        Your year at a glance — click any day to preview entries.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                        class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        @click="navYear(-1)">
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <span class="min-w-[80px] text-center text-lg font-bold text-zinc-800 dark:text-zinc-200">{{ year }}</span>
                    <button type="button"
                        class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                        @click="navYear(1)">
                        <ChevronRight class="h-4 w-4" />
                    </button>
                    <button type="button"
                        class="rounded-lg border border-zinc-200 px-3 py-1.5 text-xs text-zinc-600 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800"
                        @click="router.get('/notes/planner', { year: new Date().getFullYear() })">
                        Today
                    </button>
                </div>
            </div>

            <!-- White notebook paper -->
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">

                <!-- Legend -->
                <div class="mb-4 flex items-center gap-2 justify-end text-xs text-zinc-400 sm:mb-5">
                    <span>Less</span>
                    <div class="h-3 w-3 rounded-sm bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700"></div>
                    <div class="h-3 w-3 rounded-sm bg-violet-200 dark:bg-violet-900/40"></div>
                    <div class="h-3 w-3 rounded-sm bg-violet-400 dark:bg-violet-700/70"></div>
                    <div class="h-3 w-3 rounded-sm bg-violet-600 dark:bg-violet-500"></div>
                    <span>More</span>
                </div>

                <!-- 12 month grids -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4">
                    <div v-for="mi in 12" :key="mi"
                         class="rounded-xl border border-zinc-100 bg-zinc-50/50 p-2 dark:border-zinc-800 dark:bg-zinc-800/30 sm:p-3">
                        <div class="mb-1.5 text-[10px] font-semibold text-zinc-600 dark:text-zinc-400 sm:mb-2 sm:text-xs">
                            {{ MONTH_NAMES[mi - 1] }}
                        </div>
                        <!-- Day-of-week header -->
                        <div class="mb-0.5 grid grid-cols-7 gap-0.5 sm:mb-1">
                            <div v-for="d in ['M','T','W','T','F','S','S']" :key="d"
                                 class="text-center text-[7px] text-zinc-400 sm:text-[9px]">{{ d }}</div>
                        </div>
                        <!-- Day cells -->
                        <div class="grid grid-cols-7 gap-0.5">
                            <div v-for="_ in firstDayOfMonth(year, mi)" :key="'b' + _" />
                            <button
                                v-for="d in daysInMonth(year, mi)" :key="d"
                                type="button"
                                class="aspect-square rounded-sm text-[7px] transition-all sm:text-[9px]"
                                :class="[
                                    dateKey(year, mi, d) > todayStr
                                        ? 'bg-zinc-100 dark:bg-zinc-800 opacity-25 cursor-not-allowed'
                                        : (heatColor(yearCounts[dateKey(year, mi, d)] ?? 0) || 'bg-zinc-100 dark:bg-zinc-800') + ' cursor-pointer hover:ring-2 hover:ring-violet-300',
                                    dateKey(year, mi, d) === todayStr ? 'ring-2 ring-violet-500 font-bold' : '',
                                ]"
                                :title="dateKey(year, mi, d)"
                                @click="selectDay(dateKey(year, mi, d))"
                            >{{ d }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </NotebookLayout>

    <!-- ─ Day Detail Dialog ─────────────────────────────── -->
    <Dialog v-model:open="showDayDialog">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="font-serif italic text-zinc-700 dark:text-zinc-300">
                    <BookOpen class="mr-1.5 inline h-4 w-4" />
                    {{ formatDateFull(selectedDate) }}
                </DialogTitle>
            </DialogHeader>

            <!-- Loading -->
            <div v-if="loadingDay" class="flex items-center justify-center py-12">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-violet-300 border-t-violet-600" />
            </div>

            <!-- Empty state -->
            <div v-else-if="dayNotes.length === 0" class="flex flex-col items-center py-10">
                <svg class="mb-4 h-16 w-16 opacity-20" viewBox="0 0 80 80" fill="none">
                    <rect x="15" y="10" width="38" height="52" rx="3" stroke="currentColor" stroke-width="2"/>
                    <line x1="27" y1="28" x2="47" y2="28" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="27" y1="36" x2="47" y2="36" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="27" y1="44" x2="38" y2="44" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <p class="font-serif italic text-zinc-400">No entries on this day.</p>
            </div>

            <!-- Entries list -->
            <div v-else class="flex flex-col gap-3">
                <div
                    v-for="note in dayNotes" :key="note.id"
                    class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-800/30"
                    :class="colorBg[note.color] ?? ''"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span v-if="note.mood" class="text-sm">{{ moodEmoji[note.mood] }}</span>
                                <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 truncate">{{ note.title }}</h3>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-zinc-400">
                                <span class="flex items-center gap-1"><Clock class="h-3 w-3" />{{ readingTime(note.content) }}</span>
                            </div>
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
                    <div v-if="note.content"
                         class="mt-2 prose prose-sm max-w-none text-zinc-600 dark:prose-invert dark:text-zinc-400"
                         v-html="note.content" />
                </div>

                <!-- New Entry button (only for today) -->
                <div v-if="selectedDate === todayStr" class="flex justify-center pt-1">
                    <Button size="sm" class="gap-1.5 bg-violet-600 hover:bg-violet-700"
                        @click="router.get('/notes/new')">
                        <Plus class="h-3.5 w-3.5" />
                        Add Another Entry
                    </Button>
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
                    <Label for="planner-edit-title">Title</Label>
                    <Input id="planner-edit-title" v-model="editForm.title" class="mt-1" required />
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
