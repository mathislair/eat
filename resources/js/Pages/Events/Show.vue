<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    participation: {
        type: Object,
        default: () => ({ voted: 0, total: 0 }),
    },
    summary: {
        type: Object,
        default: null,
    },
});

const validate = () => {
    if (confirm('Close voting and reveal the summary? Votes will be frozen.')) {
        router.post(route('events.validate', props.event.id));
    }
};

// The group's pick: the most-wanted cuisine nobody vetoed. The tally is
// ordered survivors-first, so it's simply the top row — as long as it wasn't
// vetoed and someone actually wanted it.
const topPick = computed(() => {
    const top = props.summary?.nationalities?.[0];
    return top && !top.vetoed && top.wants > 0 ? top : null;
});

const copied = ref(false);

const mealEmoji = {
    breakfast: '🥐',
    lunch: '🥪',
    dinner: '🍝',
};

const formatDate = (iso) =>
    new Date(iso + 'T00:00:00').toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.event.invite_code);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch (e) {
        // Clipboard unavailable (e.g. insecure context) — ignore silently.
    }
};

const destroy = () => {
    if (confirm('Delete this event for everyone? This cannot be undone.')) {
        router.delete(route('events.destroy', props.event.id));
    }
};

const leave = () => {
    if (confirm('Leave this event?')) {
        router.delete(route('events.leave', props.event.id));
    }
};
</script>

<template>
    <Head :title="event.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-ink dark:text-cream">
                    {{ event.name }}
                </h2>
                <Link
                    :href="route('events.index')"
                    class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                >
                    Back to events
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <!-- Details -->
                <div class="card">
                    <div class="flex items-start gap-4">
                        <span class="text-5xl">{{ mealEmoji[event.meal] }}</span>
                        <div>
                            <p class="font-display text-lg font-bold text-ink dark:text-cream">
                                {{ formatDate(event.date) }}
                            </p>
                            <p class="font-semibold text-ink-muted dark:text-gray-400">
                                {{ event.meal_label }} · hosted by {{ event.creator.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Voting (open) -->
                <div v-if="event.status === 'voting'" class="card">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                                Voting open 🗳️
                            </h3>
                            <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-400">
                                {{ participation.voted }}/{{ participation.total }} voted ·
                                results hidden until the host validates
                            </p>
                        </div>
                        <span v-if="event.has_voted" class="badge badge-mint">
                            You voted
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <Link :href="route('events.vote.edit', event.id)">
                            <PrimaryButton>
                                {{ event.has_voted ? 'Edit my vote' : 'Vote' }}
                            </PrimaryButton>
                        </Link>
                        <SecondaryButton v-if="event.is_creator" type="button" @click="validate">
                            Validate &amp; reveal summary
                        </SecondaryButton>
                    </div>
                </div>

                <!-- Summary (closed) -->
                <div v-else-if="summary" class="card">
                    <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                        The verdict ✨
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-400">
                        {{ summary.participation.voted }}/{{ summary.participation.total }} voted
                    </p>

                    <!-- Decision banner -->
                    <div
                        v-if="topPick"
                        class="mt-4 rounded-blob border-3 border-ink bg-mint-300 px-5 py-4 text-ink shadow-cartoon"
                    >
                        <p class="font-display text-xs font-bold uppercase tracking-widest">
                            We're eating
                        </p>
                        <p class="font-display text-2xl font-bold">🏆 {{ topPick.name }}</p>
                        <p class="mt-0.5 text-sm font-semibold">
                            👍 {{ topPick.wants }} want{{ topPick.wants === 1 ? '' : 's' }} · vetoed by nobody
                        </p>
                    </div>
                    <div
                        v-else
                        class="mt-4 rounded-blob border-3 border-dashed border-ink/40 px-5 py-4 text-sm font-semibold text-ink-muted dark:border-cream/30 dark:text-gray-300"
                    >
                        No survivor — every wanted cuisine got vetoed. Time to flip a coin! 🪙
                    </div>

                    <h4 class="mt-5 font-display text-sm font-bold uppercase tracking-wide text-ink-muted dark:text-gray-300">
                        Cuisines
                    </h4>
                    <ol v-if="summary.nationalities.length" class="mt-2 space-y-2">
                        <li
                            v-for="(n, i) in summary.nationalities"
                            :key="n.id"
                            class="flex items-center justify-between gap-3 rounded-xl2 border-3 px-3 py-2"
                            :class="i === 0 && !n.vetoed && n.wants > 0
                                ? 'border-ink bg-sunny-200 shadow-cartoon-xs'
                                : n.vetoed
                                    ? 'border-transparent bg-berry-100 dark:bg-ink-700'
                                    : 'border-transparent bg-cream-200 dark:bg-ink-700'"
                        >
                            <span
                                class="font-semibold text-ink dark:text-cream"
                                :class="{ 'line-through opacity-70': n.vetoed }"
                            >
                                <span v-if="i === 0 && !n.vetoed && n.wants > 0">🏆 </span>
                                <span v-else-if="n.vetoed">⛔ </span>{{ n.name }}
                            </span>
                            <span class="flex items-center gap-2 text-sm font-bold text-ink-muted dark:text-gray-400">
                                <span title="Want">👍 {{ n.wants }}</span>
                                <span title="Veto">🔴 {{ n.avoids }}</span>
                                <span
                                    class="badge"
                                    :class="n.vetoed
                                        ? 'badge-berry'
                                        : n.wants > 0 ? 'badge-mint' : 'bg-cream-200 text-ink dark:bg-ink-700 dark:text-cream'"
                                >
                                    {{ n.vetoed ? 'Vetoed' : n.wants > 0 ? 'In' : '—' }}
                                </span>
                            </span>
                        </li>
                    </ol>
                    <p v-else class="mt-2 text-sm font-semibold text-ink-muted dark:text-gray-400">
                        No votes.
                    </p>

                    <div
                        v-for="(items, type) in summary.criteria"
                        :key="type"
                        class="mt-5"
                    >
                        <h4 class="font-display text-sm font-bold uppercase capitalize tracking-wide text-ink-muted dark:text-gray-300">
                            {{ type }}
                        </h4>
                        <div v-if="items.length" class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="(item, i) in items"
                                :key="item.value"
                                class="badge"
                                :class="i === 0 && !item.vetoed && item.wants > 0
                                    ? 'bg-punch-500 text-white'
                                    : item.vetoed
                                        ? 'badge-berry line-through'
                                        : 'bg-cream-200 text-ink dark:bg-ink-700 dark:text-cream'"
                            >
                                <span v-if="i === 0 && !item.vetoed && item.wants > 0">🏆</span>
                                <span v-else-if="item.vetoed">⛔</span>
                                {{ item.label }} · 👍{{ item.wants }}
                            </span>
                        </div>
                        <p v-else class="mt-2 text-sm font-semibold text-ink-muted dark:text-gray-400">—</p>
                    </div>
                </div>

                <!-- Invite -->
                <div class="card">
                    <h3 class="font-display text-sm font-bold uppercase tracking-wide text-ink-muted dark:text-gray-300">
                        Invite code
                    </h3>
                    <div class="mt-3 flex items-center gap-3">
                        <code
                            class="rounded-xl2 border-3 border-ink bg-sunny-200 px-3 py-2 font-mono text-lg font-bold tracking-widest text-ink"
                        >
                            {{ event.invite_code }}
                        </code>
                        <SecondaryButton type="button" @click="copyCode">
                            {{ copied ? 'Copied!' : 'Copy code' }}
                        </SecondaryButton>
                    </div>
                    <p class="mt-3 break-all text-xs font-semibold text-ink-muted dark:text-gray-400">
                        Share link: {{ event.join_url }}
                    </p>
                </div>

                <!-- Attendees -->
                <div class="card">
                    <h3 class="font-display text-sm font-bold uppercase tracking-wide text-ink-muted dark:text-gray-300">
                        Guests ({{ event.attendees.length }})
                    </h3>
                    <ul class="mt-3 divide-y-2 divide-dashed divide-ink/15 dark:divide-cream/15">
                        <li
                            v-for="attendee in event.attendees"
                            :key="attendee.id"
                            class="flex items-center justify-between py-2.5"
                        >
                            <span class="font-semibold text-ink dark:text-cream">
                                {{ attendee.name }}
                            </span>
                            <span
                                v-if="attendee.id === event.creator.id"
                                class="badge badge-host"
                            >
                                Host
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex justify-end">
                    <DangerButton v-if="event.is_creator" @click="destroy">
                        Delete event
                    </DangerButton>
                    <DangerButton v-else @click="leave">
                        Leave event
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
