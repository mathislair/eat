<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    restaurants: { type: Array, default: () => [] },
    match: { type: Object, default: null },
    leader: { type: Object, default: null },
    stats: { type: Object, default: () => ({ attendees: 0, finished: 0 }) },
});

// My deck: the spots I haven't swiped yet, in match-rank order.
const queue = ref(props.restaurants.filter((r) => r.mine === null));
const pointer = ref(0);
const total = props.restaurants.length;

const top = computed(() => queue.value[pointer.value] ?? null);
const peek = computed(() => queue.value[pointer.value + 1] ?? null);
const done = computed(() => pointer.value >= queue.value.length);
const swipedCount = computed(() => total - queue.value.length + pointer.value);

// ── Drag / swipe ───────────────────────────────────────────────────────────
const dragX = ref(0);
const dragging = ref(false);
const flying = ref(false);
let startX = 0;

const hint = computed(() =>
    dragX.value > 45 ? 'accept' : dragX.value < -45 ? 'reject' : null,
);
const topStyle = computed(() => ({
    transform: `translateX(${dragX.value}px) rotate(${dragX.value * 0.05}deg)`,
}));

const onDown = (e) => {
    if (flying.value || !top.value) return;
    dragging.value = true;
    startX = e.clientX;
    e.currentTarget.setPointerCapture?.(e.pointerId);
};
const onMove = (e) => {
    if (dragging.value) dragX.value = e.clientX - startX;
};
const onUp = () => {
    if (!dragging.value) return;
    dragging.value = false;
    if (dragX.value > 110) decide('accept');
    else if (dragX.value < -110) decide('reject');
    else dragX.value = 0;
};

const decide = (decision) => {
    const restaurant = top.value;
    if (!restaurant || flying.value) return;

    flying.value = true;
    dragX.value = decision === 'accept' ? 1100 : -1100;

    router.post(
        route('events.reveal.swipe', props.event.id),
        { restaurant_id: restaurant.id, decision },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['restaurants', 'match', 'leader', 'stats'],
        },
    );

    window.setTimeout(() => {
        pointer.value += 1;
        dragX.value = 0;
        flying.value = false;
    }, 260);
};

const refresh = () => router.reload({ only: ['restaurants', 'match', 'leader', 'stats'] });
</script>

<template>
    <Head :title="`Pick a place — ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-ink dark:text-cream">
                    Pick a place — {{ event.name }}
                </h2>
                <Link
                    :href="route('events.show', event.id)"
                    class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                >
                    Back to event
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl space-y-5 sm:px-6 lg:px-8">
                <!-- Empty catalogue -->
                <div v-if="!total" class="card text-center">
                    <p class="text-5xl">🍽️</p>
                    <h3 class="mt-3 font-display text-lg font-bold text-ink dark:text-cream">
                        No spots to swipe yet
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-300">
                        No restaurants matched your group's votes.
                    </p>
                </div>

                <!-- Swipe deck -->
                <template v-else-if="!done">
                    <p class="text-center font-display text-sm font-bold text-ink-muted dark:text-gray-300">
                        {{ swipedCount }} / {{ total }} swiped · swipe right to accept, left to pass
                    </p>

                    <div class="relative mx-auto h-[26rem] w-full">
                        <!-- Card behind (peek) -->
                        <div
                            v-if="peek"
                            class="swipe-card swipe-card--peek"
                        >
                            <div class="swipe-card__body">
                                <span class="badge badge-grape">#{{ peek.position }} match</span>
                                <h3 class="mt-3 font-display text-2xl font-bold text-ink dark:text-cream">
                                    {{ peek.name }}
                                </h3>
                            </div>
                        </div>

                        <!-- Top card -->
                        <div
                            v-if="top"
                            class="swipe-card"
                            :class="{ 'swipe-card--drag': dragging }"
                            :style="topStyle"
                            @pointerdown="onDown"
                            @pointermove="onMove"
                            @pointerup="onUp"
                            @pointercancel="onUp"
                        >
                            <!-- Decision stamps -->
                            <span v-show="hint === 'accept'" class="swipe-stamp swipe-stamp--yes">LOVE&nbsp;IT</span>
                            <span v-show="hint === 'reject'" class="swipe-stamp swipe-stamp--no">NOPE</span>

                            <div class="swipe-card__body">
                                <div class="flex items-center justify-between">
                                    <span class="badge badge-grape">#{{ top.position }} match</span>
                                    <span v-if="top.cuisine" class="badge badge-sky">{{ top.cuisine }}</span>
                                </div>
                                <h3 class="mt-4 font-display text-3xl font-bold text-ink dark:text-cream">
                                    {{ top.name }}
                                </h3>
                                <p v-if="top.description" class="mt-2 font-semibold text-ink-muted dark:text-gray-300">
                                    {{ top.description }}
                                </p>
                                <div v-if="top.criteria.length" class="mt-4 flex flex-wrap gap-2">
                                    <span
                                        v-for="(c, i) in top.criteria"
                                        :key="i"
                                        class="badge badge-mint"
                                    >
                                        {{ c.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-center gap-8">
                        <button
                            type="button"
                            class="swipe-btn swipe-btn--no"
                            aria-label="Pass"
                            :disabled="flying"
                            @click="decide('reject')"
                        >
                            ✕
                        </button>
                        <button
                            type="button"
                            class="swipe-btn swipe-btn--yes"
                            aria-label="Accept"
                            :disabled="flying"
                            @click="decide('accept')"
                        >
                            ♥
                        </button>
                    </div>
                </template>

                <!-- Results -->
                <template v-else>
                    <div
                        v-if="match"
                        class="card border-ink bg-mint-300 text-center text-ink shadow-cartoon-lg"
                    >
                        <p class="font-display text-sm font-bold uppercase tracking-widest">It's a match!</p>
                        <p class="mt-1 text-5xl">🎉</p>
                        <p class="mt-2 font-display text-3xl font-bold">{{ match.name }}</p>
                        <p v-if="match.cuisine" class="mt-1 font-display text-lg font-semibold">
                            {{ match.cuisine }}
                        </p>
                        <p class="mt-3 text-sm font-bold">
                            Everyone's in — that's where you're eating.
                        </p>
                    </div>

                    <div v-else class="card text-center">
                        <p class="text-5xl">🤝</p>
                        <h3 class="mt-2 font-display text-xl font-bold text-ink dark:text-cream">
                            You're all done!
                        </h3>
                        <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-300">
                            No unanimous pick yet —
                            {{ stats.finished }}/{{ stats.attendees }} guests have finished swiping.
                        </p>
                        <div
                            v-if="leader"
                            class="mx-auto mt-4 max-w-sm rounded-xl2 border-3 border-ink bg-sunny-200 px-4 py-3 text-ink shadow-cartoon-xs"
                        >
                            <p class="font-display text-xs font-bold uppercase tracking-widest">Leading so far</p>
                            <p class="font-display text-lg font-bold">{{ leader.name }}</p>
                            <p class="text-sm font-semibold">
                                👍 {{ leader.accepts }} · 👎 {{ leader.rejects }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-ghost mt-4" @click="refresh">
                            Check again 🔄
                        </button>
                    </div>

                    <!-- Full tally -->
                    <div class="card">
                        <h3 class="font-display text-sm font-bold uppercase tracking-wide text-ink-muted dark:text-gray-300">
                            Everyone's swipes
                        </h3>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="r in restaurants"
                                :key="r.id"
                                class="flex items-center justify-between gap-3 rounded-xl2 border-3 px-3 py-2"
                                :class="match && r.id === match.id
                                    ? 'border-ink bg-mint-200'
                                    : 'border-transparent bg-cream-200 dark:bg-ink-700'"
                            >
                                <span class="font-semibold text-ink dark:text-cream">
                                    <span v-if="match && r.id === match.id">🏆 </span>{{ r.name }}
                                </span>
                                <span class="flex items-center gap-2 text-sm font-bold text-ink-muted dark:text-gray-400">
                                    <span title="Accepted">👍 {{ r.accepts }}</span>
                                    <span title="Passed">👎 {{ r.rejects }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex justify-center">
                        <Link :href="route('events.show', event.id)">
                            <PrimaryButton>Back to event</PrimaryButton>
                        </Link>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
