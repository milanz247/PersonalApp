<script setup lang="ts">
import NotebookLayout from '@/layouts/NotebookLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Settings, Send, CheckCircle, AlertCircle, Zap, RefreshCw, MessageSquare } from 'lucide-vue-next';

const props = defineProps<{
    telegramBotToken: string;
    telegramChatId: string;
    hasTelegram: boolean;
    hasWebhook: boolean;
}>();

const form = useForm({
    telegram_bot_token: '',
    telegram_chat_id: props.telegramChatId,
});

const showToken   = ref(false);
const testLoading = ref(false);
const testResult  = ref<{ success: boolean; message: string } | null>(null);
const detectLoading = ref(false);
const detectResult  = ref<{ success: boolean; message: string } | null>(null);

// Laravel sets an XSRF-TOKEN cookie that can be read by JS and sent as X-XSRF-TOKEN
function getXsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function submit() {
    form.post(route('notes.settings.save'), {
        preserveScroll: true,
        onSuccess: () => {
            form.telegram_bot_token = '';
            showToken.value = false;
            testResult.value = null;
        },
    });
}

async function testConnection() {
    testLoading.value = true;
    testResult.value  = null;
    try {
        const res  = await fetch('/notes/settings/test', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': getXsrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        testResult.value = await res.json();
    } catch {
        testResult.value = { success: false, message: 'Network error. Check your connection.' };
    } finally {
        testLoading.value = false;
    }
}

const webhookRegistered = ref(props.hasWebhook);
const webhookLoading    = ref(false);
const webhookResult     = ref<{ success: boolean; message: string } | null>(null);

async function registerWebhook() {
    webhookLoading.value = true;
    webhookResult.value  = null;
    try {
        const res  = await fetch('/notes/settings/register-webhook', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': getXsrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await res.json();
        webhookResult.value = data;
        if (data.success) webhookRegistered.value = true;
    } catch {
        webhookResult.value = { success: false, message: 'Network error. Check your connection.' };
    } finally {
        webhookLoading.value = false;
    }
}

async function autoDetectChatId() {
    detectLoading.value = true;
    detectResult.value  = null;
    try {
        const res  = await fetch('/notes/settings/detect-chat-id', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': getXsrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        });
        const data = await res.json();
        if (data.success && data.chatId) {
            form.telegram_chat_id = data.chatId;
        }
        detectResult.value = data;
    } catch {
        detectResult.value = { success: false, message: 'Network error. Check your connection.' };
    } finally {
        detectLoading.value = false;
    }
}
</script>

<template>
    <Head title="Diary Settings · My Diary" />
    <NotebookLayout>
        <div class="mx-auto max-w-2xl px-4 py-6 sm:py-10">

            <div class="mb-6 sm:mb-8">
                <h1 class="font-serif text-2xl italic text-zinc-800 dark:text-zinc-100 sm:text-3xl">
                    <Settings class="mr-2 inline h-6 w-6 text-violet-500" />
                    Diary Settings
                </h1>
                <p class="mt-1 text-sm text-zinc-400">Configure integrations for your personal diary.</p>
            </div>

            <!-- Telegram Integration Card -->
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 rounded-t-2xl bg-gradient-to-r from-blue-500 to-cyan-500" />

                <div class="p-5 sm:p-8">
                    <!-- Status badge -->
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Send class="h-5 w-5 text-blue-500" />
                            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Telegram Bot</h2>
                        </div>
                        <div
                            class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
                            :class="hasTelegram
                                ? 'bg-green-50 text-green-600 dark:bg-green-950/30 dark:text-green-400'
                                : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500'"
                        >
                            <CheckCircle v-if="hasTelegram" class="h-3 w-3" />
                            <AlertCircle v-else class="h-3 w-3" />
                            {{ hasTelegram ? 'Connected' : 'Not configured' }}
                        </div>
                    </div>

                    <!-- Setup guide -->
                    <div class="mb-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm dark:border-blue-900/40 dark:bg-blue-950/20">
                        <p class="mb-2 font-semibold text-blue-700 dark:text-blue-400">How to set up — 3 steps:</p>
                        <ol class="list-decimal space-y-1 pl-5 text-blue-700 dark:text-blue-400">
                            <li>Open Telegram → search <strong>@BotFather</strong> → send <code class="rounded bg-blue-100 px-1 dark:bg-blue-900">/newbot</code> → copy the <strong>Bot Token</strong></li>
                            <li>Open your new bot → press <strong>Start</strong> (or send any message to it)</li>
                            <li>Paste the token below → click <strong>Auto-detect Chat ID</strong> → Save Settings</li>
                        </ol>
                    </div>

                    <form class="flex flex-col gap-5" @submit.prevent="submit">
                        <!-- Bot Token -->
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs font-medium uppercase tracking-wider text-zinc-500">Bot Token</Label>
                            <div class="relative">
                                <Input
                                    v-model="form.telegram_bot_token"
                                    :type="showToken ? 'text' : 'password'"
                                    :placeholder="telegramBotToken || 'Paste your bot token here…'"
                                    class="h-10 pr-20 font-mono text-sm"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-0.5 text-xs text-zinc-400 hover:text-zinc-600"
                                    @click="showToken = !showToken"
                                >
                                    {{ showToken ? 'Hide' : 'Show' }}
                                </button>
                            </div>
                            <p class="text-xs text-zinc-400">Leave blank to keep the existing token.</p>
                            <p v-if="form.errors.telegram_bot_token" class="text-xs text-red-500">{{ form.errors.telegram_bot_token }}</p>
                        </div>

                        <!-- Chat ID -->
                        <div class="flex flex-col gap-1.5">
                            <Label class="text-xs font-medium uppercase tracking-wider text-zinc-500">Chat ID</Label>
                            <div class="flex gap-2">
                                <Input
                                    v-model="form.telegram_chat_id"
                                    placeholder="e.g. 123456789"
                                    class="h-10 flex-1 font-mono text-sm"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="h-10 shrink-0 gap-1.5 text-xs"
                                    :disabled="detectLoading"
                                    @click="autoDetectChatId"
                                >
                                    <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': detectLoading }" />
                                    {{ detectLoading ? 'Detecting…' : 'Auto-detect' }}
                                </Button>
                            </div>
                            <!-- detect result -->
                            <p
                                v-if="detectResult"
                                class="text-xs font-medium"
                                :class="detectResult.success ? 'text-green-600' : 'text-red-500'"
                            >{{ detectResult.message }}</p>
                            <p v-if="!detectResult" class="text-xs text-zinc-400">
                                Paste the token first → save it → then click <strong>Auto-detect</strong>.
                            </p>
                            <p v-if="form.errors.telegram_chat_id" class="text-xs text-red-500">{{ form.errors.telegram_chat_id }}</p>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                            <!-- Test connection -->
                            <div class="flex flex-col gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="gap-1.5 text-sm border-blue-200 text-blue-600 hover:bg-blue-50 dark:border-blue-800 dark:text-blue-400"
                                    :disabled="testLoading"
                                    @click="testConnection"
                                >
                                    <Zap class="h-4 w-4" />
                                    {{ testLoading ? 'Sending…' : 'Test Connection' }}
                                </Button>
                                <p
                                    v-if="testResult"
                                    class="text-xs font-medium"
                                    :class="testResult.success ? 'text-green-600' : 'text-red-500'"
                                >{{ testResult.message }}</p>
                            </div>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="gap-1.5 bg-violet-600 hover:bg-violet-700"
                            >
                                <Send class="h-4 w-4" />
                                {{ form.processing ? 'Saving…' : 'Save Settings' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Telegram Incoming: Receive Entries from Telegram card -->
            <div class="mt-6 rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 rounded-t-2xl bg-gradient-to-r from-violet-500 to-pink-500" />

                <div class="p-5 sm:p-8">
                    <!-- Header + status -->
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <MessageSquare class="h-5 w-5 text-violet-500" />
                            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Receive from Telegram</h2>
                        </div>
                        <div
                            class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
                            :class="webhookRegistered
                                ? 'bg-green-50 text-green-600 border border-green-200 dark:bg-green-950/30 dark:border-green-700 dark:text-green-400'
                                : 'bg-zinc-100 text-zinc-400 border border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-500'"
                        >
                            <CheckCircle v-if="webhookRegistered" class="h-3 w-3" />
                            <AlertCircle v-else class="h-3 w-3" />
                            {{ webhookRegistered ? 'Webhook active' : 'Not registered' }}
                        </div>
                    </div>

                    <p class="mb-5 text-sm text-zinc-500 dark:text-zinc-400">
                        Send a text message to your bot and it will be automatically saved as a diary entry for today.
                        Configure the bot above first, then click Register.
                    </p>

                    <div class="flex flex-col gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full gap-2 border-violet-200 text-violet-600 hover:bg-violet-50 dark:border-violet-800 dark:text-violet-400 sm:w-auto"
                            :disabled="webhookLoading || !hasTelegram"
                            @click="registerWebhook"
                        >
                            <MessageSquare class="h-4 w-4" :class="{ 'animate-pulse': webhookLoading }" />
                            {{ webhookLoading ? 'Registering…' : webhookRegistered ? 'Re-register Webhook' : 'Register Webhook' }}
                        </Button>
                        <p v-if="!hasTelegram" class="text-xs text-amber-500">⚠️ Save your bot token and chat ID above first.</p>
                        <p
                            v-if="webhookResult"
                            class="text-xs font-medium"
                            :class="webhookResult.success ? 'text-green-600' : 'text-red-500'"
                        >{{ webhookResult.message }}</p>
                        <p v-if="webhookRegistered && !webhookResult" class="text-xs text-zinc-400">
                            ✅ Just send any text to your bot in Telegram — it will appear in your diary.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </NotebookLayout>
</template>
