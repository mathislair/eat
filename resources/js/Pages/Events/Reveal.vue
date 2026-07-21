<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    restaurants: { type: Array, default: () => [] },
    match: { type: Object, default: null },
    leader: { type: Object, default: null },
    stats: { type: Object, default: () => ({ attendees: 0, finished: 0 }) },
});

const CUISINE_EMOJI = {
    Italian: '🍝', Japanese: '🍣', Thai: '🍜', Indian: '🍛', Vietnamese: '🍲',
    Korean: '🍲', Chinese: '🥢', French: '🥐', Spanish: '🥘', Greek: '🥙',
    Lebanese: '🧆', Moroccan: '🍢', Turkish: '🥙', Mexican: '🌮', American: '🍔',
};
const emojiFor = (cuisine) => CUISINE_EMOJI[cuisine] ?? '🍽️';

const HEADER_TINTS = ['bg-sunny-200', 'bg-mint-200', 'bg-sky-200', 'bg-punch-200', 'bg-grape-200', 'bg-berry-200'];
const headerTint = (position) => HEADER_TINTS[(position - 1) % HEADER_TINTS.length];

// My deck: the spots I haven't swiped yet, in match-rank order.
const queue = ref(props.restaurants.filter((r) => r.mine === null));
const pointer = ref(0);
const total = props.restaurants.length;
const maxScore = Math.max(0, ...props.restaurants.map((r) => r.match_score ?? 0));

const top = computed(() => queue.value[pointer.value] ?? null);
const peek = computed(() => queue.value[pointer.value + 1] ?? null);
const done = computed(() => pointer.value >= queue.value.length);
const swipedCount = computed(() => total - queue.value.length + pointer.value);
const uniqueCuisines = computed(() => [...new Set(props.restaurants.map((r) => r.cuisine).filter(Boolean))]);

const strength = (score) => (maxScore > 0 ? Math.round(((score ?? 0) / maxScore) * 100) : 0);

// ── Workflow stage: an intro before the first swipe ─────────────────────────
const stage = ref(total && swipedCount.value === 0 && !done.value ? 'intro' : 'swiping');

// ── Drag / swipe ───────────────────────────────────────────────────────────
const cardEl = ref(null);
const dragX = ref(0);
const dragging = ref(false);
const instant = ref(false);
const flying = ref(false);
let startX = 0;

const hint = computed(() => (dragX.value > 45 ? 'accept' : dragX.value < -45 ? 'reject' : null));
const topStyle = computed(() => ({
    transform: `translateX(${dragX.value}px) rotate(${dragX.value * 0.05}deg)`,
}));
const tint = computed(() => {
    const a = Math.min(Math.abs(dragX.value) / 150, 0.5);
    return { accept: dragX.value > 0 ? a : 0, reject: dragX.value < 0 ? a : 0 };
});

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
    dragX.value = decision === 'accept' ? 1100 : -1100; // flings off-screen (transition on)

    router.post(
        route('events.reveal.swipe', props.event.id),
        { restaurant_id: restaurant.id, decision },
        { preserveScroll: true, preserveState: true, only: ['restaurants', 'match', 'leader', 'stats'] },
    );

    window.setTimeout(async () => {
        pointer.value += 1;
        instant.value = true; // snap the next card to centre with no slide
        dragX.value = 0;
        await nextTick();
        if (cardEl.value) void cardEl.value.offsetWidth; // reflow so pop-in replays
        instant.value = false;
        flying.value = false;
    }, 300);
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

                <!-- Intro -->
                <div v-else-if="stage === 'intro'" class="card animate-pop-in text-center">
                    <p class="text-6xl">🥁</p>
                    <h3 class="mt-3 font-display text-2xl font-bold text-ink dark:text-cream">
                        The votes are in!
                    </h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm font-semibold text-ink-muted dark:text-gray-300">
                        We lined up <span class="font-bold text-ink dark:text-cream">{{ total }}</span>
                        {{ total === 1 ? 'spot' : 'spots' }} your group will love — vetoed cuisines are
                        already off the table.
                    </p>
                    <div v-if="uniqueCuisines.length" class="mt-4 flex flex-wrap justify-center gap-2">
                        <span v-for="c in uniqueCuisines" :key="c" class="badge badge-sky">
                            {{ emojiFor(c) }} {{ c }}
                        </span>
                    </div>
                    <div class="mt-6 rounded-xl2 border-3 border-dashed border-ink/30 px-4 py-3 text-sm font-semibold text-ink-muted dark:border-cream/20 dark:text-gray-300">
                        Swipe <span class="font-bold text-mint-600">right to accept</span>,
                        <span class="font-bold text-berry-600">left to pass</span>. A place everyone
                        accepts is where you eat. 🍽️
                    </div>
                    <div class="mt-5">
                        <PrimaryButton @click="stage = 'swiping'">Start swiping →</PrimaryButton>
                    </div>
                </div>

                <!-- Swipe deck -->
                <template v-else-if="!done">
                    <div class="mx-auto flex max-w-xs items-center gap-3">
                        <div class="h-3 flex-1 overflow-hidden rounded-full border-3 border-ink bg-cream-200 dark:bg-ink-700">
                            <div
                                class="h-full rounded-full bg-mint-400 transition-all duration-300 ease-out"
                                :style="{ width: `${(swipedCount / total) * 100}%` }"
                            />
                        </div>
                        <span class="font-display text-sm font-bold text-ink-muted dark:text-gray-300">
                            {{ swipedCount }}/{{ total }}
                        </span>
                    </div>

                    <div class="relative mx-auto h-[27rem] w-full">
                        <!-- Card behind (peek) -->
                        <div v-if="peek" class="swipe-card swipe-card--peek">
                            <div class="swipe-card__header" :class="headerTint(peek.position)">
                                <span class="swipe-card__emoji">{{ emojiFor(peek.cuisine) }}</span>
                            </div>
                        </div>

                        <!-- Top card -->
                        <div
                            v-if="top"
                            ref="cardEl"
                            class="swipe-card"
                            :class="{ 'swipe-card--drag': dragging || instant }"
                            :style="topStyle"
                            @pointerdown="onDown"
                            @pointermove="onMove"
                            @pointerup="onUp"
                            @pointercancel="onUp"
                        >
                            <!-- Drag tint -->
                            <div class="pointer-events-none absolute inset-0 z-10 rounded-blob bg-mint-400" :style="{ opacity: tint.accept }" />
                            <div class="pointer-events-none absolute inset-0 z-10 rounded-blob bg-berry-400" :style="{ opacity: tint.reject }" />

                            <!-- Decision stamps -->
                            <span v-show="hint === 'accept'" class="swipe-stamp swipe-stamp--yes">LOVE&nbsp;IT</span>
                            <span v-show="hint === 'reject'" class="swipe-stamp swipe-stamp--no">NOPE</span>

                            <div class="swipe-card__header" :class="headerTint(top.position)">
                                <span class="swipe-card__emoji">{{ emojiFor(top.cuisine) }}</span>
                            </div>

                            <div :key="pointer" class="swipe-card__body animate-pop-in">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="badge" :class="top.position === 1 ? 'badge-host' : 'badge-grape'">
                                        {{ top.position === 1 ? '🔥 Top match' : `#${top.position} match` }}
                                    </span>
                                    <span v-if="top.cuisine" class="badge badge-sky">{{ top.cuisine }}</span>
                                </div>
                                <h3 class="mt-3 font-display text-3xl font-bold leading-tight text-ink dark:text-cream">
                                    {{ top.name }}
                                </h3>
                                <p v-if="top.description" class="mt-2 font-semibold text-ink-muted dark:text-gray-300">
                                    {{ top.description }}
                                </p>
                                <div v-if="top.criteria.length" class="mt-3 flex flex-wrap gap-2">
                                    <span v-for="(c, i) in top.criteria" :key="i" class="badge badge-mint">
                                        {{ c.label }}
                                    </span>
                                </div>
                                <div v-if="maxScore > 0" class="mt-auto pt-4">
                                    <div class="flex items-center justify-between text-xs font-bold text-ink-muted dark:text-gray-400">
                                        <span>Match strength</span>
                                        <span>{{ strength(top.match_score) }}%</span>
                                    </div>
                                    <div class="mt-1 h-2.5 overflow-hidden rounded-full border-3 border-ink bg-cream-200 dark:bg-ink-700">
                                        <div class="h-full rounded-full bg-punch-400" :style="{ width: `${strength(top.match_score)}%` }" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-center gap-8">
                        <button type="button" class="swipe-btn swipe-btn--no" aria-label="Pass" :disabled="flying" @click="decide('reject')">
                            ✕
                        </button>
                        <button type="button" class="swipe-btn swipe-btn--yes" aria-label="Accept" :disabled="flying" @click="decide('accept')">
                            ♥
                        </button>
                    </div>
                </template>

                <!-- Results -->
                <template v-else>
                    <div
                        v-if="match"
                        class="card relative animate-pop-in overflow-hidden border-ink bg-mint-300 text-center text-ink shadow-cartoon-lg"
                    >
                        <span
                            v-for="i in 9"
                            :key="i"
                            class="confetti-piece pointer-events-none absolute top-0 text-xl"
                            :style="{ left: `${i * 10}%`, animationDelay: `${i * 0.14}s` }"
                            aria-hidden="true"
                        >{{ ['🎉', '🎊', '✨', '🍽️'][i % 4] }}</span>
                        <p class="font-display text-sm font-bold uppercase tracking-widest">It's a match!</p>
                        <p class="mt-1 text-7xl">{{ emojiFor(match.cuisine) }}</p>
                        <p class="mt-2 font-display text-3xl font-bold">{{ match.name }}</p>
                        <p v-if="match.cuisine" class="mt-1 font-display text-lg font-semibold">{{ match.cuisine }}</p>
                        <p class="mt-3 text-sm font-bold">Everyone's in — that's where you're eating.</p>
                    </div>

                    <div v-else class="card text-center">
                        <p class="text-5xl">🤝</p>
                        <h3 class="mt-2 font-display text-xl font-bold text-ink dark:text-cream">You're all done!</h3>
                        <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-300">
                            No unanimous pick yet — {{ stats.finished }}/{{ stats.attendees }} guests have
                            finished swiping.
                        </p>
                        <div
                            v-if="leader"
                            class="mx-auto mt-4 max-w-sm rounded-xl2 border-3 border-ink bg-sunny-200 px-4 py-3 text-ink shadow-cartoon-xs"
                        >
                            <p class="font-display text-xs font-bold uppercase tracking-widest">Leading so far</p>
                            <p class="font-display text-lg font-bold">{{ emojiFor(leader.cuisine) }} {{ leader.name }}</p>
                            <p class="text-sm font-semibold">👍 {{ leader.accepts }} · 👎 {{ leader.rejects }}</p>
                        </div>
                        <button type="button" class="btn btn-ghost mt-4" @click="refresh">Check again 🔄</button>
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
                                    <span v-if="match && r.id === match.id">🏆 </span>{{ emojiFor(r.cuisine) }} {{ r.name }}
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
