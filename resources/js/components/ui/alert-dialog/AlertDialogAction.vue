<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AlertDialogAction, useForwardProps, type AlertDialogActionProps } from 'radix-vue';
import { buttonVariants } from '@/components/ui/button';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<AlertDialogActionProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;
    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <AlertDialogAction v-bind="forwardedProps" :class="cn(buttonVariants(), props.class)">
        <slot />
    </AlertDialogAction>
</template>
