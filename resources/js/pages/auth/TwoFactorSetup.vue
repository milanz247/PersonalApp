<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    qrCodeSvg: string;
    secret: string;
}>();

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('2fa.confirm'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthBase title="Two-Factor Authentication Setup" description="Enhance your account security by enabling Two-Factor Authentication.">
        <Head title="Setup 2FA" />

        <div class="flex flex-col items-center gap-6">
            <p class="text-sm text-center text-muted-foreground">
                Scan the QR code below with your Google Authenticator or compatible app.
            </p>

            <div class="p-4 bg-white rounded-lg shadow-sm" v-html="qrCodeSvg"></div>

            <div class="text-center">
                <p class="text-sm font-medium">Or enter this secret manually:</p>
                <code class="px-2 py-1 bg-muted rounded font-mono text-sm tracking-widest mt-1 inline-block">{{ secret }}</code>
            </div>

            <form @submit.prevent="submit" class="w-full flex flex-col gap-6 mt-2">
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
                    Verify & Enable
                </Button>
            </form>
        </div>
    </AuthBase>
</template>
