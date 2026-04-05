<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Highlight from '@tiptap/extension-highlight';
import CharacterCount from '@tiptap/extension-character-count';
import Placeholder from '@tiptap/extension-placeholder';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import {
    ChevronLeft, Bold as BoldIcon, Italic as ItalicIcon,
    Underline as UnderlineIcon, Highlighter, Quote,
    List, ListOrdered, Minus, Undo2, Redo2, Eye, EyeOff, Plus,
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

// ── Props ─────────────────────────────────────────────────────────
const props = defineProps<{
    date: string;   // YYYY-MM-DD
    notes: Note[];
}>();

// ── Date helpers ──────────────────────────────────────────────────
const MONTH_NAMES = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December',
];
const [yy, mm, dd] = props.date.split('-').map(Number);
const dateObj  = new Date(yy, mm - 1, dd);
const dayName  = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
const monthName = MONTH_NAMES[mm - 1];

// ── Future / past date locking ────────────────────────────────────
const todayStr = new Date().toISOString().split('T')[0];
const isFuture = computed(() => props.date > todayStr);
const isPast   = computed(() => props.date < todayStr);
const isLocked = ref(false);

function unlockToEdit() {
    isLocked.value = false;
    editor.value?.setEditable(true);
    setTimeout(() => editor.value?.commands.focus(), 50);
}

// ── Mood helpers ──────────────────────────────────────────────────
const MOODS = ['happy','excited','calm','sad','angry'] as const;
const moodEmoji: Record<string, string> = {
    happy: '😊', sad: '😢', angry: '😠', calm: '😌', excited: '🤩',
};

// ── Color palette ─────────────────────────────────────────────────
const COLORS = ['default','yellow','green','blue','pink','purple'] as const;
const colorDot: Record<string, string> = {
    default: '#d4d4d8', yellow: '#fde047', green: '#86efac',
    blue: '#93c5fd', pink: '#f9a8d4', purple: '#d8b4fe',
};

// ── Active note state ─────────────────────────────────────────────
const activeNoteId  = ref<number | null>(null);
const titleVal      = ref('');
const selectedMood  = ref<string | null>(null);
const selectedColor = ref('default');

function loadNote(note: Note) {
    activeNoteId.value  = note.id;
    titleVal.value      = note.title;
    selectedMood.value  = note.mood;
    selectedColor.value = note.color;
    editor.value?.commands.setContent(note.content ?? '', false);
    saveStatus.value = 'idle';
}

function startNewNote() {
    activeNoteId.value  = null;
    titleVal.value      = '';
    selectedMood.value  = null;
    selectedColor.value = 'default';
    editor.value?.commands.setContent('', false);
    saveStatus.value = 'idle';
    setTimeout(() => editor.value?.commands.focus(), 50);
}

onMounted(() => {
    if (isFuture.value) return;
    if (props.notes.length > 0) loadNote(props.notes[0]);
    else if (!isPast.value) setTimeout(() => editor.value?.commands.focus(), 120);
    if (isPast.value) {
        isLocked.value = true;
        editor.value?.setEditable(false);
    }
});

// ── Auto-save ─────────────────────────────────────────────────────
type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';
const saveStatus = ref<SaveStatus>('idle');
let saveTimer  : ReturnType<typeof setTimeout> | null = null;
let idleTimer  : ReturnType<typeof setTimeout> | null = null;

function triggerSave() {
    if (isLocked.value || isFuture.value) return;
    if (saveTimer) clearTimeout(saveTimer);
    if (idleTimer) clearTimeout(idleTimer);
    saveStatus.value = 'saving';
    saveTimer = setTimeout(performSave, 1500);
}

function getCsrf(): string {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

async function performSave() {
    const title = titleVal.value.trim() || 'Untitled';
    if (!title && !editor.value?.getText().trim()) return;     // nothing to save

    const body = JSON.stringify({
        title,
        content:   editor.value?.getHTML() ?? '',
        color:     selectedColor.value,
        note_date: props.date,
        mood:      selectedMood.value,
    });
    try {
        const url    = activeNoteId.value ? `/notes/${activeNoteId.value}` : '/notes';
        const method = activeNoteId.value ? 'PUT' : 'POST';
        const res    = await fetch(url, {
            method,
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-XSRF-TOKEN':     getCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        });
        if (!res.ok) throw new Error('fail');
        const data = await res.json();
        if (!activeNoteId.value && data.note?.id) {
            activeNoteId.value = data.note.id;
        }
        saveStatus.value = 'saved';
        idleTimer = setTimeout(() => {
            if (saveStatus.value === 'saved') saveStatus.value = 'idle';
        }, 3000);
    } catch {
        saveStatus.value = 'error';
    }
}

watch(titleVal,      () => triggerSave());
watch(selectedMood,  () => triggerSave());
watch(selectedColor, () => triggerSave());

// ── Focus mode ────────────────────────────────────────────────────
const isFocusMode = ref(false);
const tempShow    = ref(false);
let   tempTimer   : ReturnType<typeof setTimeout> | null = null;

const headerVisible = computed(() => !isFocusMode.value || tempShow.value);

function onMouseMove(e: MouseEvent) {
    if (!isFocusMode.value) return;
    if (e.clientY < 12) {
        tempShow.value = true;
        if (tempTimer) clearTimeout(tempTimer);
        tempTimer = setTimeout(() => { tempShow.value = false; }, 2500);
    }
}

// ── Word / char count ─────────────────────────────────────────────
const wordCount = computed(() => {
    const t = (editor.value?.getText() ?? '').trim();
    return t ? t.split(/\s+/).length : 0;
});
const charCount = computed(
    () => (editor.value?.storage?.characterCount?.characters?.() as number | undefined) ?? 0,
);

// ── Save label & class ────────────────────────────────────────────
const saveLabel = computed(() => {
    if (saveStatus.value === 'saving') return '● Saving…';
    if (saveStatus.value === 'saved')  return '✓ Saved';
    if (saveStatus.value === 'error')  return '✗ Error';
    return '';
});
const saveClass = computed(() => ({
    'text-amber-600': saveStatus.value === 'saving',
    'text-green-700': saveStatus.value === 'saved',
    'text-red-600':   saveStatus.value === 'error',
}));

// ── TipTap editor ─────────────────────────────────────────────────
const editor = useEditor({
    content: '',
    extensions: [
        StarterKit,
        Underline,
        Highlight.configure({ multicolor: false }),
        CharacterCount,
        Placeholder.configure({ placeholder: 'Begin writing your thoughts here…' }),
    ],
    editorProps: {
        attributes: {
            class: 'min-h-[500px] focus:outline-none',
        },
    },
    onUpdate: () => triggerSave(),
});

function tb(active: boolean) {
    return [
        'flex h-8 w-8 items-center justify-center rounded-md text-sm transition-all',
        active
            ? 'bg-[#3d3020]/15 text-[#2d2010] font-bold'
            : 'text-zinc-500 hover:bg-[#3d3020]/10 hover:text-[#3d3020]',
    ];
}

onBeforeUnmount(() => {
    if (saveTimer) clearTimeout(saveTimer);
    if (idleTimer) clearTimeout(idleTimer);
    if (tempTimer) clearTimeout(tempTimer);
    editor.value?.destroy();
});
</script>

<template>
    <Head :title="`${dayName} — My Diary`" />

    <!-- ══ PAGE WRAPPER ═══════════════════════════════════════════ -->
    <div
        class="min-h-screen transition-colors duration-700"
        :class="isFocusMode ? 'bg-[#d5cebb]' : 'bg-[#ece5d5]'"
        @mousemove="onMouseMove"
    >
        <!-- ══ STICKY HEADER + TOOLBAR (fades in focus mode) ════ -->
        <div
            class="sticky top-0 z-50 overflow-hidden transition-all duration-700"
            :style="{ maxHeight: headerVisible ? '110px' : '0px', opacity: headerVisible ? 1 : 0 }"
        >
            <!-- Nav row -->
            <div class="flex items-center gap-3 border-b border-[#b5a882] bg-[#c8b898]/90 px-5 py-2.5 backdrop-blur-sm">
                <button
                    type="button"
                    class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm text-[#3d3020] transition-colors hover:bg-[#3d3020]/10"
                    @click="router.get('/notes/planner', { view: 'day', day: date })"
                >
                    <ChevronLeft class="h-4 w-4" />
                    Back
                </button>

                <div class="hidden h-5 w-px bg-[#b5a882] sm:block" />

                <!-- Date label -->
                <div class="hidden items-baseline gap-2 sm:flex">
                    <span class="font-dancing text-2xl text-[#3d3020]">{{ dayName }},</span>
                    <span class="font-playfair text-sm italic text-[#6b5b45]">{{ monthName }} {{ dd }}, {{ yy }}</span>
                </div>

                <div class="flex-1" />

                <!-- Mood selector -->
                <div class="flex items-center gap-0.5">
                    <button
                        v-for="mood in MOODS"
                        :key="mood"
                        type="button"
                        :title="mood"
                        class="rounded-full px-2 py-0.5 text-base transition-all hover:scale-110"
                        :class="selectedMood === mood ? 'ring-1 ring-[#3d3020]/40 scale-110 bg-[#3d3020]/10' : ''"
                        @click="selectedMood = selectedMood === mood ? null : mood"
                    >{{ moodEmoji[mood] }}</button>
                </div>

                <div class="hidden h-5 w-px bg-[#b5a882] sm:block" />

                <!-- Note tabs (this day's entries) -->
                <div v-if="notes.length > 0" class="hidden items-center gap-1 sm:flex">
                    <button
                        v-for="note in notes"
                        :key="note.id"
                        type="button"
                        class="max-w-[120px] truncate rounded-full border px-2.5 py-0.5 text-[11px] transition-all"
                        :class="activeNoteId === note.id
                            ? 'border-[#3d3020] bg-[#3d3020] text-[#fdf8f0]'
                            : 'border-[#b5a882] text-[#54473a] hover:border-[#3d3020]'"
                        @click="loadNote(note)"
                    >{{ note.title }}</button>
                    <button
                        type="button"
                        class="flex items-center gap-0.5 rounded-full border border-dashed border-[#b5a882] px-2.5 py-0.5 text-[11px] text-[#54473a] transition-all hover:border-[#3d3020]"
                        @click="startNewNote()"
                    >
                        <Plus class="h-2.5 w-2.5" /> New
                    </button>
                </div>

                <!-- Focus mode button -->
                <button
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs transition-all"
                    :class="isFocusMode
                        ? 'border-[#3d3020] bg-[#3d3020] text-[#fdf8f0]'
                        : 'border-[#b5a882] text-[#3d3020] hover:bg-[#3d3020]/10'"
                    @click="isFocusMode = !isFocusMode; if (isFocusMode) tempShow = false"
                >
                    <EyeOff v-if="isFocusMode" class="h-3.5 w-3.5" />
                    <Eye    v-else              class="h-3.5 w-3.5" />
                    {{ isFocusMode ? 'Exit Focus' : 'Focus Mode' }}
                </button>
            </div>

            <!-- Toolbar row -->
            <div v-if="!isFuture" class="flex items-center gap-0.5 border-b border-[#d5c8b5] bg-[#f5f0e6]/95 px-5 py-1 backdrop-blur-sm" :class="isLocked ? 'pointer-events-none opacity-40' : ''">
                <button type="button" :class="tb(editor?.isActive('bold')      ?? false)" title="Bold"      @click="editor?.chain().focus().toggleBold().run()">      <BoldIcon      class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(editor?.isActive('italic')    ?? false)" title="Italic"    @click="editor?.chain().focus().toggleItalic().run()">    <ItalicIcon    class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(editor?.isActive('underline') ?? false)" title="Underline" @click="editor?.chain().focus().toggleUnderline().run()"> <UnderlineIcon class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(editor?.isActive('highlight') ?? false)" title="Highlight" @click="editor?.chain().focus().toggleHighlight().run()"> <Highlighter   class="h-3.5 w-3.5" /></button>
                <span class="mx-1.5 h-4 w-px bg-[#d5c8b5]" />
                <button type="button" :class="tb(editor?.isActive('blockquote')   ?? false)" title="Quote"        @click="editor?.chain().focus().toggleBlockquote().run()">    <Quote        class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(editor?.isActive('bulletList')   ?? false)" title="Bullet list"  @click="editor?.chain().focus().toggleBulletList().run()">   <List         class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(editor?.isActive('orderedList')  ?? false)" title="Numbered list" @click="editor?.chain().focus().toggleOrderedList().run()">  <ListOrdered  class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(false)"                                    title="Divider"       @click="editor?.chain().focus().setHorizontalRule().run()"> <Minus        class="h-3.5 w-3.5" /></button>
                <span class="mx-1.5 h-4 w-px bg-[#d5c8b5]" />
                <button type="button" :class="tb(false)" title="Undo" @click="editor?.chain().focus().undo().run()"><Undo2 class="h-3.5 w-3.5" /></button>
                <button type="button" :class="tb(false)" title="Redo" @click="editor?.chain().focus().redo().run()"><Redo2 class="h-3.5 w-3.5" /></button>
                <div class="flex-1" />
                <span class="font-playfair text-xs italic text-zinc-400">Words: {{ wordCount }} · Chars: {{ charCount }}</span>
                <span class="ml-4 min-w-[80px] text-right font-mono text-xs transition-all" :class="saveClass">{{ saveLabel }}</span>
            </div>
        </div>

        <!-- ══ FUTURE DATE BLOCK ════════════════════════════════ -->
        <div v-if="isFuture" class="flex min-h-[70vh] flex-col items-center justify-center gap-6 px-4">
            <div class="text-7xl select-none">🔮</div>
            <div class="text-center">
                <h2 class="font-dancing text-4xl text-[#3d3020] mb-2">Not yet written…</h2>
                <p class="font-playfair italic text-[#6b5b45] text-lg">You can't write a diary entry for the future.</p>
                <p class="mt-1 text-sm text-[#8a7a60]">{{ monthName }} {{ dd }}, {{ yy }} hasn't happened yet.</p>
            </div>
            <button
                type="button"
                class="flex items-center gap-2 rounded-xl border border-[#b5a882] bg-[#c8b898]/60 px-5 py-2.5 text-sm text-[#3d3020] transition-colors hover:bg-[#c8b898]"
                @click="router.get('/notes/planner', { view: 'day', day: date })"
            >
                <ChevronLeft class="h-4 w-4" /> Go back
            </button>
        </div>

        <!-- ══ PAPER AREA ════════════════════════════════════════ -->
        <div v-else class="flex justify-center px-4 py-10">
            <div class="flex w-full max-w-[840px]" style="filter: drop-shadow(0 8px 32px rgba(0,0,0,0.18));">

                <!-- BINDING STRIP ──────────────────────────────── -->
                <div
                    class="flex w-11 flex-shrink-0 flex-col items-center justify-start gap-[18px] rounded-l-2xl py-9"
                    style="background: linear-gradient(to right, #1c1408, #2d2418);"
                >
                    <div
                        v-for="i in 15" :key="i"
                        class="h-[22px] w-[22px] flex-shrink-0 rounded-full"
                        style="
                            background: radial-gradient(circle at 35% 35%, #6b5740, #18110a);
                            box-shadow: inset 0 2px 5px rgba(0,0,0,0.75), 0 1px 0 rgba(255,255,255,0.07);
                        "
                    />
                </div>

                <!-- PAPER ──────────────────────────────────────── -->
                <div
                    class="relative flex-1 overflow-hidden rounded-r-2xl"
                    style="background-color: #fdfcf5; box-shadow: 6px 0 20px rgba(0,0,0,0.12);"
                >
                    <!-- Ruled lines background -->
                    <div
                        class="pointer-events-none absolute inset-0"
                        style="
                            background-image: repeating-linear-gradient(
                                transparent,
                                transparent 31px,
                                #ddd0bd 31px,
                                #ddd0bd 32px
                            );
                            background-size: 100% 32px;
                        "
                    />

                    <!-- Red margin line -->
                    <div
                        class="pointer-events-none absolute bottom-0 top-0"
                        style="left: 72px; width: 1.5px; background-color: rgba(210, 90, 90, 0.40);"
                    />

                    <!-- Content (padded to sit right of margin) -->
                    <div class="relative px-8 pb-14 pt-8" style="padding-left: 90px;">

                        <!-- Page top: big day number + date string -->
                        <div class="mb-5 border-b border-[#ddd0bd] pb-4">
                            <div class="flex items-end justify-between">
                                <div>
                                    <div
                                        class="font-dancing leading-none text-[#2d2010]"
                                        style="font-size: 72px; line-height: 1.05;"
                                    >{{ dd }}</div>
                                    <div class="font-playfair text-[18px] italic text-[#8a7a60]">
                                        {{ monthName }} {{ yy }} &mdash; {{ dayName }}
                                    </div>
                                </div>
                                <!-- Mood display bubble -->
                                <div v-if="selectedMood" class="pb-1 text-4xl opacity-80" :title="selectedMood">
                                    {{ moodEmoji[selectedMood] }}
                                </div>
                            </div>
                        </div>

                        <!-- Lock banner ─────────────────────────── -->
                        <div v-if="isPast && isLocked"
                            class="mb-4 flex items-center justify-between rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm"
                        >
                            <div class="flex items-center gap-2 text-amber-700">
                                <span class="text-base">🔒</span>
                                <span class="font-playfair italic">This entry is locked — past entries are read-only.</span>
                            </div>
                            <button
                                type="button"
                                class="rounded-lg border border-amber-400 bg-white px-3 py-1 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                                @click="unlockToEdit()"
                            >Unlock to Edit</button>
                        </div>

                        <!-- Title input ─────────────────────────── -->
                        <input
                            v-model="titleVal"
                            type="text"
                            placeholder="Give this entry a title…"
                            class="mb-3 w-full bg-transparent font-dancing text-[32px] leading-snug text-[#2d2010] placeholder:text-[#c5b8a5] focus:outline-none"
                            :readonly="isLocked"
                        />

                        <!-- Editor ──────────────────────────────── -->
                        <div class="relative">
                            <EditorContent :editor="editor" class="write-editor" />
                            <div
                                v-if="isLocked"
                                class="absolute inset-0 cursor-not-allowed rounded"
                                title="Unlock to edit this entry"
                            />
                        </div>

                        <!-- Paper footer ────────────────────────── -->
                        <div class="mt-10 flex items-center justify-between border-t border-[#ddd0bd] pt-3">
                            <!-- Color palette -->
                            <div class="flex items-center gap-2">
                                <span class="font-playfair text-xs italic text-zinc-400">Color</span>
                                <button
                                    v-for="c in COLORS" :key="c"
                                    type="button"
                                    class="h-5 w-5 rounded-full border-2 transition-all hover:scale-110"
                                    :style="{ backgroundColor: colorDot[c] }"
                                    :class="selectedColor === c ? 'border-[#3d3020] scale-110' : 'border-transparent'"
                                    @click="selectedColor = c"
                                />
                            </div>
                            <!-- Word count -->
                            <span class="font-playfair text-xs italic text-zinc-400">{{ wordCount }} words</span>
                            <!-- Save status (mirrored from toolbar) -->
                            <span class="font-mono text-xs" :class="saveClass">{{ saveLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── ProseMirror (TipTap) aesthetics ──────────────────────────── */
.write-editor :deep(.ProseMirror) {
    font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
    font-size: 17px;
    line-height: 32px;
    color: #2c2416;
    outline: none;
    min-height: 500px;
    caret-color: #7c3aed;
}
.write-editor :deep(.ProseMirror > * + *) { margin-top: 0; }
.write-editor :deep(.ProseMirror p)       { margin: 0; min-height: 32px; }

.write-editor :deep(.ProseMirror blockquote) {
    border-left: 3px solid rgba(210, 90, 90, 0.5);
    padding-left: 1rem;
    font-style: italic;
    color: #6b5138;
    margin: 4px 0;
}
.write-editor :deep(.ProseMirror ul),
.write-editor :deep(.ProseMirror ol) {
    padding-left: 1.5rem;
    margin: 2px 0;
}
.write-editor :deep(.ProseMirror mark) {
    background-color: rgba(253, 224, 71, 0.55);
    border-radius: 2px;
    padding: 0 2px;
}
.write-editor :deep(.ProseMirror hr) {
    border: none;
    border-top: 1px solid #ddd0bd;
    margin: 8px 0;
}
.write-editor :deep(.ProseMirror strong)  { color: #1a1208; }
.write-editor :deep(.ProseMirror em)      { color: #4a3820; font-style: italic; }
.write-editor :deep(.ProseMirror h1),
.write-editor :deep(.ProseMirror h2),
.write-editor :deep(.ProseMirror h3) {
    font-family: 'Playfair Display', serif;
    color: #2d2010;
    font-weight: 700;
    line-height: 32px;
    margin: 0;
}

/* Placeholder */
.write-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #c0ad96;
    pointer-events: none;
    height: 0;
    font-style: italic;
}
</style>
