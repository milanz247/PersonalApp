<script setup lang="ts">
import { type ToastProviderProps, ToastProvider } from 'radix-vue';

defineProps<ToastProviderProps>();
</script>

<template>
    <ToastProvider>
        <slot />
    </ToastProvider>
</template>
