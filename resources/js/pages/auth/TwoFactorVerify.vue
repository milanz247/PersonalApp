<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { LoaderCircle, ShieldCheck } from 'lucide-vue-next';

defineProps<{
    email: string;
}>();

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('2fa.authenticate'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthBase title="Two-Factor Authentication" description="Your account is protected with two-factor authentication.">
        <Head title="Two-Factor Authentication" />

        <div class="flex flex-col items-center gap-4 mb-6">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <ShieldCheck class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <p class="text-sm text-center text-muted-foreground">
                Enter the 6-digit code from your Google Authenticator app for
                <span class="font-medium text-foreground">{{ email }}</span>
            </p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-2">
                <Label for="code">Authentication Code</Label>
                <Input
                    id="code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    required
                    autofocus
                    tabindex="1"
                    v-model="form.code"
                    placeholder="123456"
                    class="text-center tracking-widest text-lg"
                    maxlength="6"
                />
                <InputError :message="form.errors.code" />
            </div>

            <Button type="submit" class="w-full" tabindex="2" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                Verify & Login
            </Button>

            <div class="text-center">
                <Link :href="route('logout')" method="post" as="button" class="text-sm text-muted-foreground hover:text-foreground underline underline-offset-4">
                    Sign Out
                </Link>
            </div>
        </form>
    </AuthBase>
</template>
