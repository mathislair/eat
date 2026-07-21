<script setup>
import BottomNav from '@/Components/BottomNav.vue';
import InstallPrompt from '@/Components/InstallPrompt.vue';
</script>

<template>
    <div class="min-h-screen bg-cream pb-24 dark:bg-ink-900">
        <!-- Page heading (acts as the app bar for each screen) -->
        <header
            v-if="$slots.header"
            class="sticky top-0 z-30 border-b-3 border-ink bg-white/95 backdrop-blur dark:bg-ink-800/95"
        >
            <div class="mx-auto max-w-3xl px-4 py-4 sm:px-6">
                <slot name="header" />
            </div>
        </header>

        <!-- Page content -->
        <main>
            <slot />
        </main>

        <InstallPrompt bottom-offset="calc(6rem + env(safe-area-inset-bottom))" />
        <BottomNav />
    </div>
</template>
