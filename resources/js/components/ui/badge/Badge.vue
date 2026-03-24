<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { badgeVariants, type BadgeVariants } from '.';

interface BadgeProps {
    variant?: NonNullable<BadgeVariants['variant']>;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<BadgeProps>(), {
    variant: 'default',
});
</script>

<template>
    <div :class="cn(badgeVariants({ variant: props.variant }), props.class)">
        <slot />
    </div>
</template>
