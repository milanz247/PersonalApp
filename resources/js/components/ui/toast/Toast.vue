<script setup lang="ts">
import { type ToastRootEmits, type ToastRootProps, ToastRoot, useForwardPropsEmits } from 'radix-vue';
import { type HTMLAttributes, computed } from 'vue';

import { cn } from '@/lib/utils';
import { type ToastVariants, toastVariants } from '.';

const props = withDefaults(
    defineProps<
        ToastRootProps & {
            class?: HTMLAttributes['class'];
            variant?: ToastVariants['variant'];
            onOpenChange?: ((value: boolean) => void) | undefined;
        }
    >(),
    { variant: 'default' },
);

const emits = defineEmits<ToastRootEmits>();

const delegatedProps = computed(() => {
    const { class: _, variant: __, ...delegated } = props;
    return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <ToastRoot
        v-bind="forwarded"
        :class="cn(toastVariants({ variant }), props.class)"
        @update:open="emits('update:open', $event)"
    >
        <slot />
    </ToastRoot>
</template>
