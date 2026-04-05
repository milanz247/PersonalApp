<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Wallet, TrendingUp, Gem, BookOpen, ArrowRight, ShieldCheck } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const page = usePage();
const user = page.props.auth?.user;

// ─── CLOCK & GREETING LOGIC ─────────────────────────────────────
const currentTime = ref(new Date());
let timer: ReturnType<typeof setInterval>;

onMounted(() => {
    timer = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    clearInterval(timer);
});

const formattedTime = computed(() => {
    return currentTime.value.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
});

const formattedDate = computed(() => {
    return currentTime.value.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    });
});

const greeting = computed(() => {
    const hour = currentTime.value.getHours();
    let timeGreeting = '';

    if (hour < 12) timeGreeting = 'Good Morning';
    else if (hour < 18) timeGreeting = 'Good Afternoon';
    else timeGreeting = 'Good Evening';

    const name = user?.name ? user.name.split(' ')[0] : 'Milan';
    return `${timeGreeting}, ${name}`;
});

// ─── BENTO GRID DATA ──────────────────────────────────────────
const pillars = [
    {
        id: 'finance',
        title: 'Finance Tracker',
        description: 'Track expenses, manage accounts, and set monthly budgets.',
        icon: Wallet,
        colorUrl: 'text-blue-600 dark:text-blue-400',
        bgBg: 'bg-blue-100/50 dark:bg-blue-900/30',
        route: 'dashboard',
        loginRedirect: null,
    },
    // {
    //     id: 'forex',
    //     title: 'Forex Trading Hub',
    //     description: 'Log trades, analyze performance, and track P/L metrics.',
    //     icon: TrendingUp,
    //     colorUrl: 'text-violet-600 dark:text-violet-400',
    //     bgBg: 'bg-violet-100/50 dark:bg-violet-900/30',
    //     route: 'dashboard',
    //     loginRedirect: null,
    // },
    // {
    //     id: 'assets',
    //     title: 'Asset Manager',
    //     description: 'Monitor long-term wealth, vehicles, property, and gold.',
    //     icon: Gem,
    //     colorUrl: 'text-emerald-600 dark:text-emerald-400',
    //     bgBg: 'bg-emerald-100/50 dark:bg-emerald-900/30',
    //     route: 'dashboard',
    //     loginRedirect: null,
    // },
    {
        id: 'vault',
        title: 'Personal Notebook',
        description: 'Your daily diary & personal journal. Write notes, ideas, and goals for any day of the year.',
        icon: BookOpen,
        colorUrl: 'text-violet-600 dark:text-violet-400',
        bgBg: 'bg-violet-100/50 dark:bg-violet-900/30',
        route: 'notes.index',
        loginRedirect: 'notes',
    }
];
</script>

<template>
    <Head title="Personal HQ" />

    <div class="relative min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-50">

        <div class="absolute inset-0 bg-[url('https://res.cloudinary.com/dzvy8pwws/image/upload/v1714041793/noise_dypnpe.png')] opacity-[0.03] dark:opacity-[0.05] pointer-events-none"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px] pointer-events-none"></div>

        <div class="absolute right-6 top-6 z-10">
            <template v-if="!user">
                <div class="flex items-center gap-3">
                    <Link :href="route('login')">
                        <Button variant="ghost" size="sm" class="text-zinc-600 dark:text-zinc-400">
                            Sign In
                        </Button>
                    </Link>
                    <Link :href="route('register')">
                        <Button size="sm" class="shadow-sm">Get Started</Button>
                    </Link>
                </div>
            </template>
            <template v-else>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">HQ Online</span>
                    </div>
                </div>
            </template>
        </div>

        <div class="relative z-10 mx-auto flex max-w-7xl flex-col items-center justify-center px-6 py-20 min-h-screen">

            <div class="flex flex-col items-center justify-center mb-16 text-center">
                <div class="mb-6 inline-flex flex-col items-center justify-center">
                    <p class="text-sm font-medium tracking-widest text-zinc-500 dark:text-zinc-400 uppercase">
                        {{ formattedDate }}
                    </p>
                    <h2 class="mt-1 text-4xl font-light tracking-tight text-zinc-900 dark:text-zinc-100 tabular-nums">
                        {{ formattedTime }}
                    </h2>
                </div>

                <h1 class="text-5xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-6xl lg:text-7xl">
                    {{ greeting }}
                </h1>
                <p class="mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                    Welcome to your Personal Operating System. Control your wealth, track your trading edge, and secure your knowledge.
                </p>
            </div>

            <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">

                <div
                    v-for="pillar in pillars"
                    :key="pillar.id"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-zinc-200 bg-white/60 p-6 text-left shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:border-zinc-300 hover:shadow-md dark:border-zinc-800/50 dark:bg-zinc-900/50 dark:hover:border-zinc-700 dark:hover:shadow-black/50"
                >
                    <div>
                        <div
                            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110"
                            :class="pillar.bgBg"
                        >
                            <component :is="pillar.icon" class="h-6 w-6" :class="pillar.colorUrl" />
                        </div>
                        <h3 class="mb-2 text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                            {{ pillar.title }}
                        </h3>
                        <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                            {{ pillar.description }}
                        </p>
                    </div>

                    <div class="mt-8">
                        <Link
                            :href="user ? route(pillar.route) : (pillar.loginRedirect ? route('login') + '?redirect_to=' + pillar.loginRedirect : route('login'))"
                            class="inline-block w-full"
                        >
                            <Button
                                variant="outline"
                                class="w-full justify-between bg-white dark:bg-zinc-950 hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-colors"
                            >
                                <span v-if="user" class="flex items-center gap-2">
                                    Quick Access
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <ShieldCheck class="h-4 w-4" />
                                    Secure Login
                                </span>
                                <ArrowRight class="h-4 w-4 opacity-50 group-hover:opacity-100 transition-opacity" />
                            </Button>
                        </Link>
                    </div>
                </div>

            </div>

            <div class="mt-20 text-center opacity-60 hover:opacity-100 transition-opacity">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-500 tracking-wider uppercase">
                    Personal HQ System &copy; {{ new Date().getFullYear() }}
                </p>
            </div>
        </div>
    </div>
</template>
