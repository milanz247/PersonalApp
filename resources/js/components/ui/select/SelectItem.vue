<script setup lang="ts">
interface SelectItemProps {
    value: string;
    disabled?: boolean;
    class?: string;
}

withDefaults(defineProps<SelectItemProps>(), {
    disabled: false,
    class: '',
});
</script>

<template>
    <option :value="value" :disabled="disabled">
        <slot />
    </option>
</template>
