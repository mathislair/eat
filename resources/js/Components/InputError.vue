<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="font-display text-sm font-semibold text-berry-600 dark:text-berry-300">
            {{ message }}
        </p>
    </div>
</template>
