<script setup>
defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <!-- In-content page title (no sticky app bar). Optional action on the right. -->
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="min-w-0 flex-1 truncate font-display text-2xl font-bold leading-tight text-ink dark:text-cream">
            {{ title }}
        </h1>
        <slot />
    </div>
</template>
