<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useAppearance } from '@/composables/useAppearance';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { CheckCircle2, HardDrive, KeyRound, Palette, SlidersHorizontal, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// ─── Shared data ──────────────────────────────────────────────────────────────

const page     = usePage<SharedData>();
const user     = computed(() => page.props.auth.user);
const settings = computed(() => page.props.userSettings);

// ─── Breadcrumbs ──────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Settings',  href: '/settings' },
];

// ─── Tab navigation ───────────────────────────────────────────────────────────

type TabId = 'profile' | 'preferences' | 'security' | 'appearance' | 'backup';

const activeTab = ref<TabId>('profile');

const tabs: { id: TabId; label: string; icon: unknown; description: string }[] = [
    { id: 'profile',     label: 'Profile',     icon: User,             description: 'Personal information & avatar' },
    { id: 'preferences', label: 'Preferences', icon: SlidersHorizontal, description: 'Currency, timezone & format'  },
    { id: 'security',    label: 'Security',    icon: KeyRound,         description: 'Password & account security'  },
    { id: 'appearance',  label: 'Appearance',  icon: Palette,          description: 'Theme & display settings'     },
    { id: 'backup',      label: 'Backup',      icon: HardDrive,        description: 'Database backup & export'     },
];

// ─── Profile form ─────────────────────────────────────────────────────────────

const profileForm = useForm({
    name:  user.value.name,
    email: user.value.email,
});

function submitProfile() {
    profileForm.patch(route('profile.update'));
}

// ─── Avatar ───────────────────────────────────────────────────────────────────

const avatarPreview  = ref<string | null>(settings.value?.avatar_url ?? null);
const avatarInputRef = ref<HTMLInputElement | null>(null);
const avatarForm     = useForm({ avatar: null as File | null });

function onAvatarChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    avatarPreview.value = URL.createObjectURL(file);
    avatarForm.avatar   = file;
}

function submitAvatar() {
    if (!avatarForm.avatar) return;
    avatarForm.post(route('settings.avatar'));
}

// ─── Currency data ────────────────────────────────────────────────────────────

const currencies = [
    { code: 'LKR', symbol: 'Rs.',  name: 'Sri Lankan Rupee'    },
    { code: 'USD', symbol: '$',    name: 'US Dollar'            },
    { code: 'EUR', symbol: '€',    name: 'Euro'                 },
    { code: 'GBP', symbol: '£',    name: 'British Pound'        },
    { code: 'JPY', symbol: '¥',    name: 'Japanese Yen'         },
    { code: 'INR', symbol: '₹',    name: 'Indian Rupee'         },
    { code: 'AUD', symbol: 'A$',   name: 'Australian Dollar'    },
    { code: 'CAD', symbol: 'C$',   name: 'Canadian Dollar'      },
    { code: 'MYR', symbol: 'RM',   name: 'Malaysian Ringgit'    },
    { code: 'SGD', symbol: 'S$',   name: 'Singapore Dollar'     },
    { code: 'AED', symbol: 'د.إ',  name: 'UAE Dirham'           },
    { code: 'SAR', symbol: 'ر.س',  name: 'Saudi Riyal'          },
    { code: 'PKR', symbol: '₨',    name: 'Pakistani Rupee'      },
    { code: 'BDT', symbol: '৳',    name: 'Bangladeshi Taka'     },
    { code: 'PHP', symbol: '₱',    name: 'Philippine Peso'      },
    { code: 'THB', symbol: '฿',    name: 'Thai Baht'            },
    { code: 'VND', symbol: '₫',    name: 'Vietnamese Dong'      },
    { code: 'IDR', symbol: 'Rp',   name: 'Indonesian Rupiah'    },
    { code: 'CNY', symbol: '¥',    name: 'Chinese Yuan'         },
    { code: 'CHF', symbol: 'Fr',   name: 'Swiss Franc'          },
    { code: 'ZAR', symbol: 'R',    name: 'South African Rand'   },
    { code: 'NGN', symbol: '₦',    name: 'Nigerian Naira'       },
];

const timezones = [
    'UTC',
    'Asia/Colombo',     'Asia/Kolkata',     'Asia/Dhaka',
    'Asia/Karachi',     'Asia/Kathmandu',   'Asia/Dubai',
    'Asia/Riyadh',      'Asia/Singapore',   'Asia/Kuala_Lumpur',
    'Asia/Bangkok',     'Asia/Jakarta',     'Asia/Manila',
    'Asia/Tokyo',       'Asia/Seoul',       'Asia/Shanghai',
    'Asia/Hong_Kong',   'Asia/Yangon',
    'Australia/Sydney', 'Australia/Melbourne',
    'Pacific/Auckland', 'Pacific/Fiji',
    'Europe/London',    'Europe/Paris',     'Europe/Berlin',    'Europe/Moscow',
    'Africa/Nairobi',   'Africa/Cairo',     'Africa/Lagos',     'Africa/Johannesburg',
    'America/New_York', 'America/Chicago',  'America/Denver',   'America/Los_Angeles',
    'America/Toronto',  'America/Sao_Paulo',
];

const dateFormats = [
    { value: 'DD/MM/YYYY',   example: '25/03/2026'   },
    { value: 'MM/DD/YYYY',   example: '03/25/2026'   },
    { value: 'YYYY-MM-DD',   example: '2026-03-25'   },
    { value: 'DD-MM-YYYY',   example: '25-03-2026'   },
    { value: 'YYYY/MM/DD',   example: '2026/03/25'   },
    { value: 'MMM DD, YYYY', example: 'Mar 25, 2026' },
];

// ─── Preferences form ─────────────────────────────────────────────────────────

const prefForm = useForm({
    currency_symbol: settings.value?.currency_symbol ?? 'Rs.',
    currency_code:   settings.value?.currency_code   ?? 'LKR',
    timezone:        settings.value?.timezone        ?? 'Asia/Colombo',
    date_format:     settings.value?.date_format     ?? 'DD/MM/YYYY',
});

function onCurrencyChange() {
    const found = currencies.find(c => c.code === prefForm.currency_code);
    if (found) prefForm.currency_symbol = found.symbol;
}

function submitPreferences() {
    prefForm.patch(route('settings.preferences'));
}

// ─── Password form ────────────────────────────────────────────────────────────

const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.put(route('password.update'), {
        onSuccess: () => passwordForm.reset(),
    });
}

// ─── Appearance ───────────────────────────────────────────────────────────────

const { appearance, updateAppearance } = useAppearance();

type AppearanceValue = 'light' | 'dark' | 'system';

const appearanceOptions: { value: AppearanceValue; label: string; desc: string }[] = [
    { value: 'light',  label: '☀️ Light',  desc: 'Clean, bright interface.' },
    { value: 'dark',   label: '🌙 Dark',   desc: 'Easy on the eyes at night.' },
    { value: 'system', label: '💻 System', desc: 'Follows your OS preference.' },
];

// ─── Backup ───────────────────────────────────────────────────────────────────

const backupLoading = ref(false);

function runBackup() {
    backupLoading.value = true;
    router.post(route('backup.run'), {}, {
        onFinish: () => { backupLoading.value = false; },
    });
}
</script>

<template>
    <Head title="Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">

            <!-- Page heading -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Settings</h1>
                <p class="text-muted-foreground text-sm">Manage your account, preferences and security.</p>
            </div>

            <div class="flex flex-col gap-6 md:flex-row">

                <!-- ─── Left nav (vertical tabs) ──────────────────────────── -->
                <aside class="w-full shrink-0 md:w-56">
                    <nav class="flex flex-row gap-1 overflow-x-auto pb-1 md:flex-col md:pb-0">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors"
                            :class="activeTab === tab.id
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                            @click="activeTab = tab.id"
                        >
                            <component :is="tab.icon" class="h-4 w-4 shrink-0" />
                            <div class="hidden md:block">
                                <div>{{ tab.label }}</div>
                                <div
                                    class="text-xs font-normal leading-none mt-0.5"
                                    :class="activeTab === tab.id ? 'text-primary-foreground/70' : 'text-muted-foreground/70'"
                                >
                                    {{ tab.description }}
                                </div>
                            </div>
                            <span class="md:hidden">{{ tab.label }}</span>
                        </button>
                    </nav>
                </aside>

                <!-- ─── Content area ──────────────────────────────────────── -->
                <div class="min-w-0 flex-1">

                    <!-- ══════════════════════════════════════════════════════
                         PROFILE TAB
                    ══════════════════════════════════════════════════════ -->
                    <div v-if="activeTab === 'profile'" class="grid gap-5">

                        <!-- Avatar -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Profile Photo</CardTitle>
                                <CardDescription>Upload a photo to personalize your account.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <!-- Preview -->
                                    <div class="relative h-24 w-24 shrink-0">
                                        <img
                                            v-if="avatarPreview"
                                            :src="avatarPreview"
                                            alt="Avatar preview"
                                            class="h-24 w-24 rounded-full border-2 border-border object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-24 w-24 items-center justify-center rounded-full border-2 border-border bg-muted text-3xl font-bold text-muted-foreground"
                                        >
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>

                                    <!-- Upload controls -->
                                    <div class="grid gap-2">
                                        <input
                                            ref="avatarInputRef"
                                            type="file"
                                            accept="image/jpeg,image/png,image/gif,image/webp"
                                            class="hidden"
                                            @change="onAvatarChange"
                                        />
                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="avatarInputRef?.click()"
                                            >
                                                Choose Photo
                                            </Button>
                                            <Button
                                                v-if="avatarForm.avatar"
                                                type="button"
                                                size="sm"
                                                :disabled="avatarForm.processing"
                                                @click="submitAvatar"
                                            >
                                                {{ avatarForm.processing ? 'Uploading…' : 'Upload' }}
                                            </Button>
                                        </div>
                                        <p class="text-muted-foreground text-xs">JPG, PNG, GIF or WebP · Max 2 MB</p>
                                        <p v-if="avatarForm.errors.avatar" class="text-destructive text-xs">{{ avatarForm.errors.avatar }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Basic info -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Personal Information</CardTitle>
                                <CardDescription>Update your display name and email address.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitProfile" class="grid gap-4">
                                    <div class="grid gap-1.5">
                                        <Label for="s-name">Full Name</Label>
                                        <Input id="s-name" v-model="profileForm.name" required />
                                        <p v-if="profileForm.errors.name" class="text-destructive text-xs">{{ profileForm.errors.name }}</p>
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="s-email">Email Address</Label>
                                        <Input id="s-email" v-model="profileForm.email" type="email" required />
                                        <p v-if="profileForm.errors.email" class="text-destructive text-xs">{{ profileForm.errors.email }}</p>
                                    </div>
                                    <div class="flex">
                                        <Button type="submit" :disabled="profileForm.processing">
                                            {{ profileForm.processing ? 'Saving…' : 'Save Changes' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         PREFERENCES TAB
                    ══════════════════════════════════════════════════════ -->
                    <div v-else-if="activeTab === 'preferences'">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Preferences</CardTitle>
                                <CardDescription>Configure your currency, timezone and date display format.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitPreferences" class="grid gap-6">

                                    <!-- Currency section -->
                                    <div class="grid gap-3">
                                        <div class="grid gap-0.5">
                                            <span class="text-sm font-medium">Currency</span>
                                            <span class="text-muted-foreground text-xs">Used for all money displays across the app.</span>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div class="grid gap-1.5">
                                                <Label for="s-ccode">Currency</Label>
                                                <select
                                                    id="s-ccode"
                                                    v-model="prefForm.currency_code"
                                                    class="border-input bg-background ring-offset-background focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                                    @change="onCurrencyChange"
                                                >
                                                    <option v-for="c in currencies" :key="c.code" :value="c.code">
                                                        {{ c.code }} — {{ c.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            <div class="grid gap-1.5">
                                                <Label for="s-csym">Symbol</Label>
                                                <Input
                                                    id="s-csym"
                                                    v-model="prefForm.currency_symbol"
                                                    placeholder="Rs."
                                                    maxlength="10"
                                                />
                                                <p v-if="prefForm.errors.currency_symbol" class="text-destructive text-xs">{{ prefForm.errors.currency_symbol }}</p>
                                            </div>
                                        </div>

                                        <!-- Live preview -->
                                        <div class="flex items-center gap-2 rounded-md bg-muted/50 px-3 py-2 text-sm">
                                            <CheckCircle2 class="h-4 w-4 text-green-500" />
                                            Preview:
                                            <strong class="font-mono">{{ prefForm.currency_symbol }} 1,500.00</strong>
                                        </div>
                                    </div>

                                    <div class="border-t" />

                                    <!-- Timezone -->
                                    <div class="grid gap-1.5">
                                        <Label for="s-tz">Timezone</Label>
                                        <select
                                            id="s-tz"
                                            v-model="prefForm.timezone"
                                            class="border-input bg-background ring-offset-background focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                        >
                                            <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                                        </select>
                                        <p class="text-muted-foreground text-xs">All dates/times are stored as UTC and displayed in your selected timezone.</p>
                                        <p v-if="prefForm.errors.timezone" class="text-destructive text-xs">{{ prefForm.errors.timezone }}</p>
                                    </div>

                                    <!-- Date format -->
                                    <div class="grid gap-1.5">
                                        <Label for="s-dfmt">Date Format</Label>
                                        <select
                                            id="s-dfmt"
                                            v-model="prefForm.date_format"
                                            class="border-input bg-background ring-offset-background focus:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus:ring-2 focus:ring-offset-2 focus:outline-none"
                                        >
                                            <option v-for="fmt in dateFormats" :key="fmt.value" :value="fmt.value">
                                                {{ fmt.value }}  (e.g. {{ fmt.example }})
                                            </option>
                                        </select>
                                        <p v-if="prefForm.errors.date_format" class="text-destructive text-xs">{{ prefForm.errors.date_format }}</p>
                                    </div>

                                    <div class="flex">
                                        <Button type="submit" :disabled="prefForm.processing">
                                            {{ prefForm.processing ? 'Saving…' : 'Save Preferences' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         SECURITY TAB
                    ══════════════════════════════════════════════════════ -->
                    <div v-else-if="activeTab === 'security'">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Change Password</CardTitle>
                                <CardDescription>Use a long, unique password to keep your account secure.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form @submit.prevent="submitPassword" class="grid gap-4">
                                    <div class="grid gap-1.5">
                                        <Label for="s-curpass">Current Password</Label>
                                        <Input
                                            id="s-curpass"
                                            v-model="passwordForm.current_password"
                                            type="password"
                                            autocomplete="current-password"
                                            required
                                        />
                                        <p v-if="passwordForm.errors.current_password" class="text-destructive text-xs">{{ passwordForm.errors.current_password }}</p>
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="s-newpass">New Password</Label>
                                        <Input
                                            id="s-newpass"
                                            v-model="passwordForm.password"
                                            type="password"
                                            autocomplete="new-password"
                                            required
                                        />
                                        <p v-if="passwordForm.errors.password" class="text-destructive text-xs">{{ passwordForm.errors.password }}</p>
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="s-confpass">Confirm New Password</Label>
                                        <Input
                                            id="s-confpass"
                                            v-model="passwordForm.password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            required
                                        />
                                    </div>
                                    <div class="flex">
                                        <Button type="submit" :disabled="passwordForm.processing">
                                            {{ passwordForm.processing ? 'Updating…' : 'Update Password' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         APPEARANCE TAB
                    ══════════════════════════════════════════════════════ -->
                    <div v-else-if="activeTab === 'appearance'">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Appearance</CardTitle>
                                <CardDescription>Choose your preferred theme. Changes apply instantly.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <button
                                        v-for="opt in appearanceOptions"
                                        :key="opt.value"
                                        type="button"
                                        class="flex flex-col items-center gap-3 rounded-xl border-2 p-4 text-center transition-all"
                                        :class="appearance === opt.value
                                            ? 'border-primary bg-primary/5 shadow-sm'
                                            : 'border-border hover:border-muted-foreground/30 hover:bg-muted/40'"
                                        @click="updateAppearance(opt.value)"
                                    >
                                        <!-- Theme swatch -->
                                        <div
                                            class="h-14 w-full rounded-lg border shadow-sm overflow-hidden"
                                            :class="{
                                                'bg-white': opt.value === 'light',
                                                'bg-zinc-900': opt.value === 'dark',
                                            }"
                                        >
                                            <div
                                                v-if="opt.value === 'system'"
                                                class="flex h-full"
                                            >
                                                <div class="h-full w-1/2 bg-white" />
                                                <div class="h-full w-1/2 bg-zinc-900" />
                                            </div>
                                        </div>

                                        <div>
                                            <div class="text-sm font-medium">{{ opt.label }}</div>
                                            <div class="text-muted-foreground mt-0.5 text-xs">{{ opt.desc }}</div>
                                        </div>

                                        <div
                                            v-if="appearance === opt.value"
                                            class="flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground"
                                        >
                                            <CheckCircle2 class="h-3 w-3" />
                                            Active
                                        </div>
                                    </button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- ══════════════════════════════════════════════════════
                         BACKUP TAB
                    ══════════════════════════════════════════════════════ -->
                    <div v-else-if="activeTab === 'backup'">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Database Backup</CardTitle>
                                <CardDescription>Create a backup of your database. Backups are stored on the server.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="grid gap-4">
                                    <div class="rounded-md bg-muted/50 px-3 py-2 text-sm text-muted-foreground">
                                        This will create a database-only backup using the server's backup system. Backups include all your accounts, transactions, budgets, and settings.
                                    </div>

                                    <Button @click="runBackup" :disabled="backupLoading">
                                        {{ backupLoading ? 'Running Backup…' : 'Backup Now' }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
