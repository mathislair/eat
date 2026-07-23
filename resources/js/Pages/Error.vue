<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: { type: Number, required: true },
    message: { type: String, default: null },
});

// On-brand copy per HTTP status. `message` (when present) overrides the default
// body — that's how a policy's own denial reason reaches the screen.
const PRESETS = {
    403: { emoji: '🚫', title: 'Not allowed', body: "You don't have access to this page." },
    404: { emoji: '🧭', title: 'Page not found', body: "We couldn't find that page." },
    419: { emoji: '⏳', title: 'Session expired', body: 'Your session timed out — please try again.' },
    500: { emoji: '💥', title: 'Something broke', body: 'Something went wrong on our end.' },
    503: { emoji: '🔧', title: 'Back in a bit', body: "We're doing a little maintenance." },
};

const preset = computed(() => PRESETS[props.status] ?? PRESETS[500]);
const body = computed(() => props.message || preset.value.body);
</script>

<template>
    <Head :title="`${status} — ${preset.title}`" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-cream px-6 py-16 text-center dark:bg-ink-900">
        <div class="card max-w-md">
            <div class="animate-float text-6xl" aria-hidden="true">{{ preset.emoji }}</div>

            <p class="mt-4 font-display text-sm font-bold uppercase tracking-widest text-ink-muted">
                Error {{ status }}
            </p>
            <h1 class="mt-1 font-display text-3xl font-bold">{{ preset.title }}</h1>
            <p class="mt-3 text-ink-soft dark:text-cream/80">{{ body }}</p>

            <Link :href="route('home')" class="mt-6 inline-block">
                <button class="btn btn-primary">Take me home</button>
            </Link>
        </div>
    </div>
</template>
