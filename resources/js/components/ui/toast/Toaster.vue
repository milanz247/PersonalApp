<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import Toast from './Toast.vue';
import ToastClose from './ToastClose.vue';
import ToastDescription from './ToastDescription.vue';
import ToastProvider from './ToastProvider.vue';
import ToastTitle from './ToastTitle.vue';
import ToastViewport from './ToastViewport.vue';

const { toasts, dismiss } = useToast();
</script>

<template>
    <ToastProvider>
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            v-bind="toast"
            @update:open="(open) => { if (!open) dismiss(toast.id) }"
        >
            <div class="grid gap-1">
                <ToastTitle v-if="toast.title">
                    {{ toast.title }}
                </ToastTitle>
                <ToastDescription v-if="toast.description">
                    {{ toast.description }}
                </ToastDescription>
            </div>
            <ToastClose />
        </Toast>
        <ToastViewport />
    </ToastProvider>
</template>
