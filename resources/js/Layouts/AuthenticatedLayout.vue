<script setup>
import BottomNav from '@/Components/BottomNav.vue';
import InstallPrompt from '@/Components/InstallPrompt.vue';
</script>

<template>
    <div class="min-h-screen bg-cream pb-24 dark:bg-ink-900">
        <!-- No global app bar: screens are content-first with an in-content
             title. The only sticky header is opted into by a page (the vote
             stepper) via the #header slot. -->
        <header
            v-if="$slots.header"
            class="sticky top-0 z-30 border-b-3 border-ink bg-white/95 backdrop-blur dark:bg-ink-800/95"
            style="padding-top: env(safe-area-inset-top)"
        >
            <div class="mx-auto max-w-2xl px-4 sm:px-6">
                <slot name="header" />
            </div>
        </header>

        <!-- Single content container — every screen shares its width & padding. -->
        <main
            class="mx-auto max-w-2xl px-4 pb-6 sm:px-6"
            :class="$slots.header ? 'pt-4' : 'pt-[calc(env(safe-area-inset-top)+1.25rem)]'"
        >
            <slot />
        </main>

        <InstallPrompt bottom-offset="calc(6rem + env(safe-area-inset-bottom))" />
        <BottomNav />
    </div>
</template>
