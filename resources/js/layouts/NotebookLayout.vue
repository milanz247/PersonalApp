<script setup lang="ts">
import { Toaster } from '@/components/ui/toast';
import { useToast } from '@/composables/useToast';
import { Link, usePage, router } from '@inertiajs/vue3';
import type { SharedData } from '@/types';
import { BookOpen, LogOut, CalendarDays, LayoutDashboard, PenSquare, Settings } from 'lucide-vue-next';
import { watch, computed } from 'vue';

const page = usePage<SharedData>();
const { toast } = useToast();
const currentPath = computed(() => page.url);

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) toast({ title: 'Saved', description: message, variant: 'default' });
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) toast({ title: 'Error', description: message, variant: 'destructive' });
    },
    { immediate: true },
);

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-zinc-50 font-sans antialiased dark:bg-zinc-950">

        <!-- Top nav bar -->
        <header class="sticky top-0 z-40 border-b border-zinc-200 bg-white/80 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-950/80">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-3 py-2.5 sm:px-4 sm:py-3">

                <!-- Brand -->
                <Link href="/notes" class="flex items-center gap-2 text-violet-600 dark:text-violet-400 shrink-0">
                    <BookOpen class="h-5 w-5" />
                    <span class="text-sm font-semibold tracking-tight hidden xs:inline sm:inline">My Diary</span>
                </Link>

                <!-- Right actions -->
                <nav class="flex items-center gap-0.5">
                    <Link
                        href="/notes"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs transition-colors sm:gap-1.5 sm:px-3"
                        :class="currentPath === '/notes'
                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <LayoutDashboard class="h-4 w-4 sm:h-3.5 sm:w-3.5" />
                        <span class="hidden sm:inline">Dashboard</span>
                    </Link>

                    <Link
                        href="/notes/planner"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs transition-colors sm:gap-1.5 sm:px-3"
                        :class="currentPath?.startsWith('/notes/planner')
                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <CalendarDays class="h-4 w-4 sm:h-3.5 sm:w-3.5" />
                        <span class="hidden sm:inline">Planner</span>
                    </Link>

                    <Link
                        href="/notes/new"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs transition-colors sm:gap-1.5 sm:px-3"
                        :class="currentPath?.startsWith('/notes/new')
                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <PenSquare class="h-4 w-4 sm:h-3.5 sm:w-3.5" />
                        <span class="hidden sm:inline">New Entry</span>
                    </Link>

                    <Link
                        href="/notes/settings"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs transition-colors sm:gap-1.5 sm:px-3"
                        :class="currentPath?.startsWith('/notes/settings')
                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300'
                            : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
                    >
                        <Settings class="h-4 w-4 sm:h-3.5 sm:w-3.5" />
                        <span class="hidden sm:inline">Settings</span>
                    </Link>

                    <div class="mx-1 hidden h-5 w-px bg-zinc-200 dark:bg-zinc-700 lg:block" />

                    <span class="hidden text-xs text-zinc-500 dark:text-zinc-400 lg:block">
                        Milan Madusanka Senarathna
                    </span>

                    <button
                        type="button"
                        class="flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs text-zinc-600 transition-colors hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 sm:gap-1.5 sm:px-3"
                        @click="logout"
                    >
                        <LogOut class="h-4 w-4 sm:h-3.5 sm:w-3.5" />
                        <span class="hidden sm:inline">Sign out</span>
                    </button>
                </nav>
            </div>
        </header>

        <!-- Page content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-12 border-t border-zinc-200 py-6 dark:border-zinc-800 sm:mt-16 sm:py-8">
            <div class="flex flex-col items-center justify-center gap-2 px-4 text-zinc-400 dark:text-zinc-600">
                <div class="flex items-center gap-2 font-serif text-xs italic sm:text-sm">
                    <BookOpen class="h-3 w-3" />
                    <span>Captured moments, one day at a time.</span>
                </div>
                <div class="text-[10px] uppercase tracking-widest">
                    &copy; {{ new Date().getFullYear() }} Milan Madusanka Senarathna &nbsp;·&nbsp; Personal Diary
                </div>
            </div>
        </footer>
    </div>

    <Toaster />
</template>
