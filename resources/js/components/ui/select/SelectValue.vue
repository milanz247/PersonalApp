<script setup lang="ts">
interface SelectValueProps {
    placeholder?: string;
    class?: string;
}

withDefaults(defineProps<SelectValueProps>(), {
    placeholder: 'Select an option',
    class: '',
});
</script>

<template>
    <!-- This component is just a placeholder in the SelectTrigger hierarchy -->
    <!-- The actual select element is rendered by the Select component -->
</template>
