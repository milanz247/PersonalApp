<script setup lang="ts">
interface SelectContentProps {
    class?: string;
}

withDefaults(defineProps<SelectContentProps>(), {
    class: '',
});
</script>

<template>
    <slot />
</template>
