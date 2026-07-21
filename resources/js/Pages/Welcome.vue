<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth?.user ?? null);

const steps = [
    { emoji: '🗳️', title: 'Vote your cravings', text: 'Everyone taps what they want — and vetoes what they really don’t.' },
    { emoji: '🚫', title: 'One veto is enough', text: 'A single “surely not” takes a cuisine off the table for the whole group.' },
    { emoji: '🔥', title: 'Swipe the shortlist', text: 'We rank the best-matching restaurants. Swipe like it’s a date.' },
    { emoji: '🎉', title: 'It’s a match', text: 'The first spot everyone accepts is where you’re eating. Done.' },
];
</script>

<template>
    <Head title="eat — decide where to eat, together" />

    <div class="min-h-screen bg-cream dark:bg-ink-900">
        <!-- Top bar -->
        <header class="mx-auto flex max-w-5xl items-center justify-between px-5 py-5">
            <span class="badge badge-host text-sm">🍴 eat</span>
            <div class="flex items-center gap-2">
                <template v-if="user">
                    <Link :href="route('events.index')"><button class="btn btn-primary">Open the app</button></Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="font-display text-sm font-semibold text-ink hover:underline dark:text-cream">
                        Log in
                    </Link>
                    <Link :href="route('register')"><button class="btn btn-primary">Sign up</button></Link>
                </template>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative mx-auto max-w-3xl overflow-hidden px-5 pb-8 pt-8 text-center sm:pt-14">
            <div class="pointer-events-none absolute inset-0 -z-0">
                <span class="animate-float absolute left-4 top-6 text-4xl sm:text-5xl">🍜</span>
                <span class="animate-float absolute right-5 top-16 text-4xl sm:text-5xl" style="animation-delay: 0.6s">🌮</span>
                <span class="animate-float absolute bottom-4 left-8 text-4xl sm:text-5xl" style="animation-delay: 1.1s">🍣</span>
                <span class="animate-float absolute bottom-8 right-8 text-4xl sm:text-5xl" style="animation-delay: 0.3s">🍕</span>
            </div>
            <div class="relative">
                <h1 class="font-display text-4xl font-bold leading-tight text-ink dark:text-cream sm:text-6xl">
                    Decide where to<br />eat, <span class="text-punch-500">together</span>.
                </h1>
                <p class="mx-auto mt-4 max-w-md font-display text-lg font-medium text-ink-muted dark:text-gray-300">
                    Stop the “I don’t know, where do you wanna go?” loop. Vote, veto, and
                    swipe your way to a place the whole group is happy with.
                </p>
                <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link :href="user ? route('events.index') : route('register')" class="w-full sm:w-auto">
                        <button class="btn btn-primary w-full text-base">
                            {{ user ? 'Go to your events' : 'Start deciding — it’s free' }}
                        </button>
                    </Link>
                    <Link v-if="!user" :href="route('login')" class="w-full sm:w-auto">
                        <button class="btn btn-ghost w-full text-base">I already have an account</button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="mx-auto max-w-3xl px-5 py-8">
            <h2 class="mb-5 text-center font-display text-sm font-bold uppercase tracking-widest text-ink-muted dark:text-gray-400">
                How it works
            </h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div v-for="(step, i) in steps" :key="i" class="card flex items-start gap-4">
                    <span class="text-4xl">{{ step.emoji }}</span>
                    <div>
                        <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                            {{ step.title }}
                        </h3>
                        <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-300">
                            {{ step.text }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-3xl px-5 pb-16">
            <div class="card bg-punch-500 text-center text-white">
                <h2 class="font-display text-2xl font-bold">Hungry yet? 🍽️</h2>
                <p class="mx-auto mt-2 max-w-sm font-display font-medium text-white/90">
                    Create an event, share the code, and let everyone weigh in.
                </p>
                <Link :href="user ? route('events.create') : route('register')" class="mt-5 inline-block">
                    <button class="btn btn-secondary text-base">
                        {{ user ? 'Create an event' : 'Get started' }}
                    </button>
                </Link>
            </div>
        </section>

        <footer class="pb-10 text-center text-xs font-semibold text-ink-muted dark:text-gray-500">
            🍴 eat — group dining, decided.
        </footer>
    </div>
</template>
