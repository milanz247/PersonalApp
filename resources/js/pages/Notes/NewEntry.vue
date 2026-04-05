<script setup lang="ts">
import NotebookLayout from '@/layouts/NotebookLayout.vue';
import TiptapEditor from '@/components/TiptapEditor.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, Save, Smile, Send, AlertTriangle } from 'lucide-vue-next';

const props = defineProps<{ date: string; hasTelegram: boolean }>();

const page = usePage();

const moodOptions = [
    { value: 'happy',   emoji: '😊', label: 'Happy' },
    { value: 'excited', emoji: '🤩', label: 'Excited' },
    { value: 'calm',    emoji: '😌', label: 'Calm' },
    { value: 'sad',     emoji: '😢', label: 'Sad' },
    { value: 'angry',   emoji: '😠', label: 'Angry' },
];

const colorOptions = [
    { value: 'default', bg: 'bg-zinc-200',   ring: 'ring-zinc-400' },
    { value: 'yellow',  bg: 'bg-yellow-300',  ring: 'ring-yellow-500' },
    { value: 'green',   bg: 'bg-green-300',   ring: 'ring-green-500' },
    { value: 'blue',    bg: 'bg-blue-300',    ring: 'ring-blue-500' },
    { value: 'pink',    bg: 'bg-pink-300',    ring: 'ring-pink-500' },
    { value: 'purple',  bg: 'bg-purple-300',  ring: 'ring-purple-500' },
];

const form = useForm({
    title: '',
    content: '',
    color: 'default',
    note_date: props.date,
    mood: null as string | null,
});

// Auto-generate title from date when submitting without a title
function buildTitle(): string {
    if (form.title.trim()) return form.title.trim();
    const d = new Date(form.note_date);
    return d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}

const saved         = ref(false);
const telegramState = ref<'sent' | 'failed' | 'none' | null>(null);

const savedMessage = computed(() => {
    if (!saved.value) return '';
    if (telegramState.value === 'sent')   return '✅ Entry saved and sent to Telegram!';
    if (telegramState.value === 'failed') return '⚠️ Entry saved, but Telegram send failed.';
    return '✅ Entry saved!';
});

function submit() {
    if (!form.title.trim()) form.title = buildTitle();
    form.post(route('notes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            saved.value = true;
            const flash = (page.props.flash as { telegram_sent?: boolean | null });
            if (flash.telegram_sent === true)       telegramState.value = 'sent';
            else if (flash.telegram_sent === false) telegramState.value = 'failed';
            else                                    telegramState.value = 'none';
            setTimeout(() => {
                router.get('/notes');
            }, telegramState.value === 'sent' ? 2000 : 1200);
        },
    });
}

function selectMood(value: string) {
    form.mood = form.mood === value ? null : value;
}
</script>

<template>
    <Head title="New Entry · My Diary" />
    <NotebookLayout>
        <div class="mx-auto max-w-3xl px-4 py-6 sm:py-10">

            <!-- Back link -->
            <button
                type="button"
                class="mb-6 flex items-center gap-1.5 text-sm text-zinc-500 transition-colors hover:text-violet-600"
                @click="router.get('/notes')"
            >
                <ArrowLeft class="h-4 w-4" />
                Back to Dashboard
            </button>

            <!-- Card -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

                <!-- Top accent -->
                <div class="h-1 rounded-t-2xl bg-gradient-to-r from-violet-500 to-purple-500" />

                <div class="p-5 sm:p-8">

                    <!-- Save / Telegram notification -->
                    <Transition
                        enter-active-class="transition-all duration-500"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div
                            v-if="saved"
                            class="mb-5 flex items-center gap-2.5 rounded-xl px-4 py-3 text-sm font-medium"
                            :class="telegramState === 'sent'
                                ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-300'
                                : telegramState === 'failed'
                                    ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-300'
                                    : 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-950/30 dark:border-green-800 dark:text-green-300'"
                        >
                            <Send v-if="telegramState === 'sent'" class="h-4 w-4 shrink-0" />
                            <AlertTriangle v-else-if="telegramState === 'failed'" class="h-4 w-4 shrink-0" />
                            <span v-else class="text-base">✅</span>
                            {{ savedMessage }}
                        </div>
                    </Transition>

                    <!-- Header -->
                    <div class="mb-6 sm:mb-8">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h1 class="font-serif text-2xl italic text-zinc-800 dark:text-zinc-100 sm:text-3xl">
                                <Smile class="mr-2 inline h-6 w-6 text-violet-500" />
                                New Entry
                            </h1>
                            <!-- Telegram status badge -->
                            <div
                                class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
                                :class="hasTelegram
                                    ? 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-400'
                                    : 'bg-zinc-100 text-zinc-400 border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-500'"
                            >
                                <Send class="h-3 w-3" />
                                {{ hasTelegram ? 'Will send to Telegram' : 'Telegram not connected' }}
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-zinc-400">Write your thoughts — use bullet points for quick point-wise notes.</p>
                    </div>

                    <form class="flex flex-col gap-5" @submit.prevent="submit">

                        <!-- Date + Mood row -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1.5">
                                <Label class="text-xs font-medium uppercase tracking-wider text-zinc-500">Date</Label>
                                <Input
                                    type="date"
                                    v-model="form.note_date"
                                    :max="new Date().toISOString().split('T')[0]"
                                    required
                                    class="h-10"
                                />
                                <p v-if="form.errors.note_date" class="text-xs text-red-500">{{ form.errors.note_date }}</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <Label class="text-xs font-medium uppercase tracking-wider text-zinc-500">Mood</Label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="m in moodOptions" :key="m.value"
                                        type="button"
                                        class="flex h-10 w-10 items-center justify-center rounded-xl border-2 text-xl transition-all hover:scale-110"
                                        :class="form.mood === m.value
                                            ? 'border-violet-500 bg-violet-50 dark:bg-violet-950/30 scale-110 shadow-sm'
                                            : 'border-zinc-200 dark:border-zinc-700'"
                                        :title="m.label"
                                        @click="selectMood(m.value)"
                                    >{{ m.emoji }}</button>
                                </div>
                            </div>
                        </div>

                        <!-- Content (Rich Text) -->
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs font-medium uppercase tracking-wider text-zinc-500">
                                Write your thoughts…
                            </Label>
                            <p class="text-xs text-zinc-400">
                                Use the bullet list <span class="inline-flex items-center rounded bg-zinc-100 px-1 dark:bg-zinc-800">☰</span> button for point-wise entries
                            </p>
                            <TiptapEditor v-model="form.content" placeholder="Start writing… use bullet points for quick notes" />
                            <p v-if="form.errors.content" class="text-xs text-red-500">{{ form.errors.content }}</p>
                        </div>

                        <!-- Color -->
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs font-medium uppercase tracking-wider text-zinc-500">Color Tag</Label>
                            <div class="flex gap-2.5">
                                <button
                                    v-for="c in colorOptions" :key="c.value"
                                    type="button"
                                    class="h-7 w-7 rounded-full border-2 transition-all hover:scale-110"
                                    :class="[
                                        c.bg,
                                        form.color === c.value
                                            ? 'border-violet-500 scale-110 shadow-md'
                                            : 'border-transparent',
                                    ]"
                                    @click="form.color = c.value"
                                />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.get('/notes')"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="form.processing || saved"
                                class="gap-1.5 bg-violet-600 hover:bg-violet-700 min-w-[120px]"
                            >
                                <Save class="h-4 w-4" />
                                {{ form.processing ? 'Saving…' : saved ? 'Saved!' : 'Save Entry' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </NotebookLayout>
</template>
