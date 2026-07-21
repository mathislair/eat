<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PreferenceTile from '@/Components/PreferenceTile.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    nationalities: { type: Array, default: () => [] },
    criteriaTypes: { type: Array, default: () => [] },
    ballot: { type: Object, required: true },
});

const search = ref('');

// The ballot IS the wire format: a map of option → preference, with neutral
// simply left out. Nothing to transform on submit.
const form = useForm({
    nationalities: { ...props.ballot.nationalities },
    criteria: Object.fromEntries(
        props.criteriaTypes.map((group) => [
            group.type,
            { ...(props.ballot.criteria?.[group.type] ?? {}) },
        ]),
    ),
});

// ── Steps ────────────────────────────────────────────────────────────────
// One step for cuisines, one per criteria family, then a review screen.
const STEP_EMOJI = { price: '💶', diet: '🥗', style: '🌶️' };

const steps = computed(() => [
    { key: 'cuisines', kind: 'cuisines', emoji: '🌍', label: 'Cuisines' },
    ...props.criteriaTypes.map((group) => ({
        key: group.type,
        kind: 'criteria',
        group,
        emoji: STEP_EMOJI[group.type] ?? '🍴',
        label: group.label,
    })),
    { key: 'review', kind: 'review', emoji: '✅', label: 'Review' },
]);

const stepIndex = ref(0);
const direction = ref('next');
const current = computed(() => steps.value[stepIndex.value]);
const isLast = computed(() => stepIndex.value === steps.value.length - 1);
const progress = computed(() => ((stepIndex.value + 1) / steps.value.length) * 100);

const goTo = (i) => {
    if (i < 0 || i >= steps.value.length || i === stepIndex.value) return;
    direction.value = i > stepIndex.value ? 'next' : 'prev';
    stepIndex.value = i;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
const next = () => goTo(stepIndex.value + 1);
const back = () => goTo(stepIndex.value - 1);

// ── Preference bindings ────────────────────────────────────────────────────
const filteredNationalities = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q
        ? props.nationalities.filter((n) => n.name.toLowerCase().includes(q))
        : props.nationalities;
});

const natState = (id) => form.nationalities[id] ?? 'neutral';
const setNat = (id, value) => {
    if (value === 'neutral') delete form.nationalities[id];
    else form.nationalities[id] = value;
};

const critState = (type, value) => form.criteria[type]?.[value] ?? 'neutral';
const setCrit = (type, value, nextValue) => {
    if (nextValue === 'neutral') delete form.criteria[type][value];
    else form.criteria[type][value] = nextValue;
};

const countPref = (pref) => {
    let n = Object.values(form.nationalities).filter((v) => v === pref).length;
    for (const group of Object.values(form.criteria)) {
        n += Object.values(group).filter((v) => v === pref).length;
    }
    return n;
};
const wantCount = computed(() => countPref('want'));
const avoidCount = computed(() => countPref('avoid'));

// ── Review recap ──────────────────────────────────────────────────────────
const natName = (id) => props.nationalities.find((n) => String(n.id) === String(id))?.name ?? id;
const critLabel = (type, value) => {
    const group = props.criteriaTypes.find((g) => g.type === type);
    return group?.options.find((o) => o.value === value)?.label ?? value;
};

const splitPrefs = (entries, labelFn) => {
    const wants = [];
    const avoids = [];
    for (const [key, pref] of entries) {
        (pref === 'want' ? wants : avoids).push(labelFn(key));
    }
    return { wants, avoids };
};

// Per-step recap for the review screen — each block links back to its step so
// a voter can jump straight in and tweak it.
const recap = computed(() =>
    steps.value
        .filter((s) => s.kind !== 'review')
        .map((s, i) => ({
            index: i,
            emoji: s.emoji,
            label: s.label,
            ...(s.kind === 'cuisines'
                ? splitPrefs(Object.entries(form.nationalities), natName)
                : splitPrefs(
                      Object.entries(form.criteria[s.group.type] ?? {}),
                      (v) => critLabel(s.group.type, v),
                  )),
        })),
);

const isEmpty = computed(() => wantCount.value === 0 && avoidCount.value === 0);

const submit = () => form.post(route('events.vote.store', props.event.id));
</script>

<template>
    <Head :title="`Vote — ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold leading-tight text-ink dark:text-cream">
                    Vote — {{ event.name }}
                </h2>
                <Link
                    :href="route('events.hub', event.id)"
                    class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                >
                    Back to event
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6">
                <!-- Progress + legend -->
                <div class="card">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-display text-sm font-bold text-ink-muted dark:text-gray-300">
                            Step {{ stepIndex + 1 }} of {{ steps.length }}
                        </p>
                        <div class="flex items-center gap-2">
                            <span v-if="wantCount" class="badge badge-mint">🟢 {{ wantCount }}</span>
                            <span v-if="avoidCount" class="badge badge-berry">🔴 {{ avoidCount }}</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-2 h-3.5 overflow-hidden rounded-full border-3 border-ink bg-cream-200 dark:bg-ink-700">
                        <div
                            class="h-full rounded-full bg-mint-400 transition-all duration-300 ease-out"
                            :style="{ width: `${progress}%` }"
                        />
                    </div>

                    <!-- Step dots -->
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="(s, i) in steps"
                            :key="s.key"
                            type="button"
                            class="wizard-dot"
                            :class="{
                                'wizard-dot--active': i === stepIndex,
                                'wizard-dot--done': i < stepIndex,
                            }"
                            :title="s.label"
                            :aria-label="`Go to ${s.label}`"
                            @click="goTo(i)"
                        >
                            <span aria-hidden="true">{{ s.emoji }}</span>
                        </button>
                    </div>
                </div>

                <!-- Step card -->
                <div class="card overflow-hidden">
                    <Transition :name="direction === 'next' ? 'step-next' : 'step-prev'" mode="out-in">
                        <div :key="current.key">
                            <!-- Heading -->
                            <div class="flex items-center gap-3">
                                <span class="text-4xl">{{ current.emoji }}</span>
                                <div>
                                    <h3 class="font-display text-xl font-bold text-ink dark:text-cream">
                                        {{ current.kind === 'review' ? 'Review your ballot' : current.label }}
                                    </h3>
                                    <p class="text-sm font-semibold text-ink-muted dark:text-gray-300">
                                        <template v-if="current.kind === 'cuisines'">
                                            Tap to cycle 🟢 want → 🔴 veto. A single 🔴 rules a cuisine out for everyone.
                                        </template>
                                        <template v-else-if="current.kind === 'criteria'">
                                            How do you feel about each {{ current.label.toLowerCase() }} option?
                                        </template>
                                        <template v-else>
                                            Happy with it? Submit when you're ready.
                                        </template>
                                    </p>
                                </div>
                            </div>

                            <!-- Cuisines -->
                            <div v-if="current.kind === 'cuisines'" class="mt-4">
                                <TextInput
                                    v-model="search"
                                    type="search"
                                    class="block w-full"
                                    placeholder="Search cuisines…"
                                />
                                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                    <PreferenceTile
                                        v-for="n in filteredNationalities"
                                        :key="n.id"
                                        :label="n.name"
                                        :model-value="natState(n.id)"
                                        @update:model-value="(v) => setNat(n.id, v)"
                                    />
                                </div>
                                <p
                                    v-if="!filteredNationalities.length"
                                    class="mt-4 text-sm font-semibold text-ink-muted dark:text-gray-400"
                                >
                                    No cuisine matches “{{ search }}”.
                                </p>
                            </div>

                            <!-- Criteria -->
                            <div v-else-if="current.kind === 'criteria'" class="mt-4">
                                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                                    <PreferenceTile
                                        v-for="option in current.group.options"
                                        :key="option.value"
                                        :label="option.label"
                                        :model-value="critState(current.group.type, option.value)"
                                        @update:model-value="(v) => setCrit(current.group.type, option.value, v)"
                                    />
                                </div>
                            </div>

                            <!-- Review -->
                            <div v-else class="mt-5 space-y-3">
                                <!-- Headline -->
                                <div
                                    class="rounded-xl2 border-3 border-ink px-4 py-3 shadow-cartoon-xs"
                                    :class="isEmpty ? 'bg-sunny-200' : 'bg-mint-200'"
                                >
                                    <p class="font-display text-base font-bold text-ink">
                                        {{ isEmpty ? "🤷 You're easy — anything goes" : "🎉 You're all set!" }}
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold text-ink/80">
                                        <template v-if="isEmpty">
                                            You haven't picked anything, so you'll count as neutral on
                                            everything. Tap Edit to add a craving or a veto.
                                        </template>
                                        <template v-else>
                                            {{ wantCount }} to love · {{ avoidCount }} to veto. Tap Edit to
                                            tweak, then submit.
                                        </template>
                                    </p>
                                </div>

                                <!-- Per-category breakdown -->
                                <div
                                    v-for="section in recap"
                                    :key="section.label"
                                    class="rounded-xl2 border-3 border-ink bg-cream-200 p-4 dark:bg-ink-700"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="font-display text-sm font-bold text-ink dark:text-cream">
                                            {{ section.emoji }} {{ section.label }}
                                        </p>
                                        <button
                                            type="button"
                                            class="font-display text-xs font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                                            @click="goTo(section.index)"
                                        >
                                            Edit
                                        </button>
                                    </div>
                                    <div
                                        v-if="section.wants.length || section.avoids.length"
                                        class="mt-2 flex flex-wrap gap-2"
                                    >
                                        <span
                                            v-for="(label, i) in section.wants"
                                            :key="`w${i}`"
                                            class="badge badge-mint"
                                        >
                                            🟢 {{ label }}
                                        </span>
                                        <span
                                            v-for="(label, i) in section.avoids"
                                            :key="`a${i}`"
                                            class="badge badge-berry"
                                        >
                                            🔴 {{ label }}
                                        </span>
                                    </div>
                                    <p
                                        v-else
                                        class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-300"
                                    >
                                        No preference — you're neutral here.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Nav -->
                    <div class="mt-6 flex items-center justify-between gap-3 border-t-3 border-dashed border-ink/15 pt-4 dark:border-cream/15">
                        <Link
                            v-if="stepIndex === 0"
                            :href="route('events.hub', event.id)"
                            class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                        >
                            Cancel
                        </Link>
                        <button v-else type="button" class="btn btn-ghost" @click="back">
                            ← Back
                        </button>

                        <button v-if="!isLast" type="button" class="btn btn-primary" @click="next">
                            Continue →
                        </button>
                        <PrimaryButton v-else :disabled="form.processing" @click="submit">
                            Submit my vote 🎉
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
