<script setup lang="ts">
interface SelectTriggerProps {
    class?: string;
}

withDefaults(defineProps<SelectTriggerProps>(), {
    class: '',
});

// This is handled by wrapping select in the parent Select component
</script>

<template>
    <slot />
</template>
