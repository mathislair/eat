<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageTitle from '@/Components/PageTitle.vue';
import PreferenceTile from '@/Components/PreferenceTile.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    nationalities: { type: Array, default: () => [] },
    criteriaTypes: { type: Array, default: () => [] },
    preferences: { type: Object, required: true },
    status: { type: String, default: null },
});

const CRITERIA_EMOJI = { price: '💶', diet: '🥗', style: '🌶️' };

const search = ref('');

// Same wire shape as a ballot: a map of option → preference, neutral omitted.
const form = useForm({
    nationalities: { ...props.preferences.nationalities },
    criteria: Object.fromEntries(
        props.criteriaTypes.map((group) => [
            group.type,
            { ...(props.preferences.criteria?.[group.type] ?? {}) },
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

const submit = () =>
    form.put(route('preferences.update'), { preserveScroll: true });
</script>

<template>
    <Head title="Food preferences" />

    <AuthenticatedLayout>
        <PageTitle title="Food preferences" />

        <div class="space-y-4">
            <!-- Intro -->
            <div class="card">
                <div class="flex items-start gap-3">
                    <span class="text-4xl">😋</span>
                    <div>
                        <h3 class="font-display text-xl font-bold text-ink dark:text-cream">
                            What do you like?
                        </h3>
                        <p class="mt-0.5 text-sm font-semibold text-ink-muted dark:text-gray-300">
                            Tap to cycle 🟢 love → 🔴 never. We use this to
                            pre-fill your votes — and if you forget to vote, it
                            still counts for you. A single 🔴 is a hard veto.
                        </p>
                    </div>
                </div>

                <div
                    v-if="wantCount || avoidCount"
                    class="mt-3 flex flex-wrap items-center gap-2"
                >
                    <span v-if="wantCount" class="badge badge-mint">🟢 {{ wantCount }} loved</span>
                    <span v-if="avoidCount" class="badge badge-berry">🔴 {{ avoidCount }} vetoed</span>
                </div>

                <p
                    v-if="status === 'preferences-updated'"
                    class="mt-3 rounded-xl2 border-3 border-ink bg-mint-200 px-4 py-2 font-display text-sm font-bold text-ink"
                >
                    ✅ Preferences saved!
                </p>
            </div>

            <!-- Cuisines -->
            <div class="card">
                <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                    🌍 Cuisines
                </h3>
                <TextInput
                    v-model="search"
                    type="search"
                    class="mt-3 block w-full"
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

            <!-- Criteria families -->
            <div
                v-for="group in criteriaTypes"
                :key="group.type"
                class="card"
            >
                <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                    {{ CRITERIA_EMOJI[group.type] ?? '🍴' }} {{ group.label }}
                </h3>
                <div class="mt-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                    <PreferenceTile
                        v-for="option in group.options"
                        :key="option.value"
                        :label="option.label"
                        :model-value="critState(group.type, option.value)"
                        @update:model-value="(v) => setCrit(group.type, option.value, v)"
                    />
                </div>
            </div>

            <!-- Save -->
            <div class="flex justify-end">
                <PrimaryButton :disabled="form.processing" @click="submit">
                    Save preferences 💾
                </PrimaryButton>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
