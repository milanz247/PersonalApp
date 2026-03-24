<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { progressVariants, type ProgressVariants } from '.';

interface ProgressProps {
    modelValue?: number;
    max?: number;
    variant?: NonNullable<ProgressVariants['variant']>;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<ProgressProps>(), {
    modelValue: 0,
    max: 100,
    variant: 'default',
});

const percentage = computed(() => Math.min(100, Math.max(0, (props.modelValue / props.max) * 100)));
</script>

<template>
    <div
        :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-primary/20', props.class)"
        role="progressbar"
        :aria-valuenow="modelValue"
        :aria-valuemin="0"
        :aria-valuemax="max"
    >
        <div
            :class="cn(progressVariants({ variant: props.variant }), 'rounded-full')"
            :style="{ width: `${percentage}%` }"
        />
    </div>
</template>
