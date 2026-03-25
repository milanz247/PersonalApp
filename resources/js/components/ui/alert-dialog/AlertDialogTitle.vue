<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AlertDialogTitle, useForwardProps, type AlertDialogTitleProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<AlertDialogTitleProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;
    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <AlertDialogTitle v-bind="forwardedProps" :class="cn('text-lg font-semibold leading-none tracking-tight', props.class)">
        <slot />
    </AlertDialogTitle>
</template>
