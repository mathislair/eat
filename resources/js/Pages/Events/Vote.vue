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
const setCrit = (type, value, next) => {
    if (next === 'neutral') delete form.criteria[type][value];
    else form.criteria[type][value] = next;
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
const hasPicks = computed(() => wantCount.value + avoidCount.value > 0);

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
                    :href="route('events.show', event.id)"
                    class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                >
                    Back to event
                </Link>
            </div>
        </template>

        <div class="py-12">
            <form @submit.prevent="submit" class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <!-- How it works -->
                <div class="card bg-punch-50 dark:bg-ink-800">
                    <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                        Set your cravings 🍽️
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-ink-muted dark:text-gray-300">
                        Tap each option to say how you feel. Tap again to change your
                        mind. When the host closes voting, the most-wanted option wins
                        — but a single 🔴 vetoes it for the whole group, so use it
                        wisely.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="pref-legend">
                            <span class="pref-legend__dot pref-legend__dot--want">✓</span>
                            I want
                        </span>
                        <span class="pref-legend">
                            <span class="pref-legend__dot pref-legend__dot--avoid">✕</span>
                            Surely not
                        </span>
                        <span class="pref-legend">
                            <span class="pref-legend__dot">–</span>
                            Neutral
                        </span>
                    </div>
                </div>

                <!-- Nationalities -->
                <div class="card">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                            Cuisines
                        </h3>
                        <div class="flex items-center gap-2">
                            <span v-if="wantCount" class="badge badge-mint">🟢 {{ wantCount }}</span>
                            <span v-if="avoidCount" class="badge badge-berry">🔴 {{ avoidCount }}</span>
                        </div>
                    </div>
                    <TextInput
                        v-model="search"
                        type="search"
                        class="mt-3 block w-full"
                        placeholder="Search cuisines…"
                    />
                    <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3 md:grid-cols-4">
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
                <div v-for="group in criteriaTypes" :key="group.type" class="card">
                    <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                        {{ group.label }}
                    </h3>
                    <div class="mt-3 grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                        <PreferenceTile
                            v-for="option in group.options"
                            :key="option.value"
                            :label="option.label"
                            :model-value="critState(group.type, option.value)"
                            @update:model-value="(v) => setCrit(group.type, option.value, v)"
                        />
                    </div>
                </div>

                <!-- Action bar -->
                <div class="sticky bottom-4 z-10">
                    <div
                        class="panel flex flex-col items-center justify-between gap-3 bg-cream-50/95 backdrop-blur dark:bg-ink-800/95 sm:flex-row"
                    >
                        <p class="text-sm font-bold text-ink-muted dark:text-gray-300">
                            <template v-if="hasPicks">
                                {{ wantCount }} craving{{ wantCount === 1 ? '' : 's' }} ·
                                {{ avoidCount }} no-go{{ avoidCount === 1 ? '' : 's' }}
                            </template>
                            <template v-else>
                                No picks yet — neutral means “I'm easy”.
                            </template>
                        </p>
                        <div class="flex items-center gap-4">
                            <Link
                                :href="route('events.show', event.id)"
                                class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Submit my vote
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
