<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

interface SelectProps {
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<SelectProps>(), {
    modelValue: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function onChange(e: Event) {
    emit('update:modelValue', (e.target as HTMLSelectElement).value);
}
</script>

<template>
    <select
        :value="modelValue"
        :class="
            cn(
                'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
        @change="onChange"
    >
        <slot />
    </select>
</template>
