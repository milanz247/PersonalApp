<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Wallet, Landmark } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

const page = usePage();
const user = page.props.auth?.user;
</script>
<template>
    <Head title="Personal Finance Suite" />

    <div class="flex min-h-screen flex-col bg-zinc-50 dark:bg-zinc-950">

        <main class="flex flex-1 flex-col items-center justify-center px-6 py-16">
            <div class="mb-12 text-center">
                <p class="mb-3 text-sm font-medium uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                    Welcome to Milan's Personal Finance Manager
                </p>
                <h1 class="text-4xl font-bold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-5xl">
                    Financial Control Center
                </h1>
                <p class="mt-4 max-w-xl text-base text-zinc-500 dark:text-zinc-400">
                    One place to track your daily finances and grow your long-term wealth.
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <template v-if="user">
                        <Link :href="route('dashboard')">
                            <Button>Go to Dashboard</Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')">
                            <Button variant="outline">Log in</Button>
                        </Link>
                        <Link :href="route('register')">
                            <Button>Get Started</Button>
                        </Link>
                    </template>
                </div>
            </div>

            <div class="grid w-full max-w-3xl grid-cols-1 gap-6 sm:grid-cols-2">

                <Card class="flex flex-col border-zinc-200 shadow-sm dark:border-zinc-800">
                    <CardHeader class="pb-4">
                        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950">
                            <Wallet class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <CardTitle class="text-lg">Expense &amp; Income Tracker</CardTitle>
                        <CardDescription class="text-sm text-zinc-500 dark:text-zinc-400">
                            Manage your daily spending, set budgets, and view detailed financial reports.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1" />
                    <CardFooter>
                        <Link :href="user ? route('dashboard') : route('login')" class="w-full">
                            <Button class="w-full">
                                {{ user ? 'Go to Dashboard' : 'Login to Start' }}
                            </Button>
                        </Link>
                    </CardFooter>
                </Card>

                <Card class="flex flex-col border-zinc-200 shadow-sm dark:border-zinc-800">
                    <CardHeader class="pb-4">
                        <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950">
                            <Landmark class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <CardTitle class="text-lg">Net Worth &amp; Asset Manager</CardTitle>
                        <CardDescription class="text-sm text-zinc-500 dark:text-zinc-400">
                            Track your long-term wealth including property, vehicles, gold, and investments.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1" />
                    <CardFooter>
                        <Link :href="user ? route('dashboard') : route('login')" class="w-full">
                            <Button class="w-full" variant="outline">
                                {{ user ? 'Manage Assets' : 'Login to Access' }}
                            </Button>
                        </Link>
                    </CardFooter>
                </Card>

            </div>
        </main>

        <footer class="border-t border-zinc-200 py-6 dark:border-zinc-800">
            <p class="text-center text-xs text-zinc-400 dark:text-zinc-600">
                &copy; {{ new Date().getFullYear() }} Personal Finance Suite. All rights reserved.
            </p>
        </footer>

    </div>
</template>
