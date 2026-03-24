<script setup lang="ts">
import { type ToastViewportProps, ToastViewport } from 'radix-vue';
import { type HTMLAttributes, computed } from 'vue';

import { cn } from '@/lib/utils';

const props = defineProps<ToastViewportProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;
    return delegated;
});
</script>

<template>
    <ToastViewport
        v-bind="delegatedProps"
        :class="
            cn(
                'fixed top-0 right-0 z-[100] flex max-h-screen w-full flex-col gap-2 p-4 sm:top-4 sm:right-4 md:max-w-[420px]',
                props.class,
            )
        "
    />
</template>
