<script setup>
import BottomNav from '@/Components/BottomNav.vue';
import InstallPrompt from '@/Components/InstallPrompt.vue';
</script>

<template>
    <div class="min-h-screen bg-cream pb-24 dark:bg-ink-900">
        <!-- Sticky app bar. Most pages fill it with <PageHeader> (a fixed-height
             title row); a page may also supply its own layout (e.g. the vote
             stepper). Same container width & padding either way. -->
        <header
            v-if="$slots.header"
            class="sticky top-0 z-30 border-b-3 border-ink bg-white/95 backdrop-blur dark:bg-ink-800/95"
        >
            <div class="mx-auto max-w-2xl px-4 sm:px-6">
                <slot name="header" />
            </div>
        </header>

        <!-- Single content container — every screen shares its width & padding. -->
        <main class="mx-auto max-w-2xl px-4 py-5 sm:px-6">
            <slot />
        </main>

        <InstallPrompt bottom-offset="calc(6rem + env(safe-area-inset-bottom))" />
        <BottomNav />
    </div>
</template>
