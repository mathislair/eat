<script setup>
defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <!-- Standard app-bar row: fixed height, title left, optional action right. -->
    <div class="flex h-16 items-center justify-between gap-3">
        <h2 class="min-w-0 flex-1 truncate text-lg font-bold leading-tight text-ink dark:text-cream">
            {{ title }}
        </h2>
        <slot />
    </div>
</template>
