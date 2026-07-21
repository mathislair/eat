<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

const form = useForm({
    nationalities: [...props.ballot.nationalities],
    criteria: JSON.parse(JSON.stringify(props.ballot.criteria)),
});

const filteredNationalities = computed(() => {
    const q = search.value.trim().toLowerCase();
    return q
        ? props.nationalities.filter((n) => n.name.toLowerCase().includes(q))
        : props.nationalities;
});

const toggleNationality = (id) => {
    const i = form.nationalities.indexOf(id);
    i === -1 ? form.nationalities.push(id) : form.nationalities.splice(i, 1);
};

const toggleCriterion = (type, value) => {
    const list = form.criteria[type] ?? (form.criteria[type] = []);
    const i = list.indexOf(value);
    i === -1 ? list.push(value) : list.splice(i, 1);
};

const isChecked = (type, value) => (form.criteria[type] ?? []).includes(value);

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
                <!-- Nationalities -->
                <div class="card">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                            Nationalities
                        </h3>
                        <span class="badge badge-grape">
                            {{ form.nationalities.length }} selected
                        </span>
                    </div>
                    <TextInput
                        v-model="search"
                        type="search"
                        class="mt-3 block w-full"
                        placeholder="Search…"
                    />
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="n in filteredNationalities"
                            :key="n.id"
                            class="flex cursor-pointer items-center gap-2 rounded-xl2 px-2 py-1.5 transition-colors hover:bg-cream-200 dark:hover:bg-ink-700"
                        >
                            <input
                                type="checkbox"
                                :checked="form.nationalities.includes(n.id)"
                                class="checkbox"
                                @change="toggleNationality(n.id)"
                            />
                            <span class="text-sm font-semibold text-ink dark:text-cream">{{ n.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Criteria -->
                <div
                    v-for="group in criteriaTypes"
                    :key="group.type"
                    class="card"
                >
                    <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                        {{ group.label }}
                    </h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="option in group.options"
                            :key="option.value"
                            type="button"
                            class="chip"
                            :class="{ 'chip-selected': isChecked(group.type, option.value) }"
                            @click="toggleCriterion(group.type, option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('events.show', event.id)"
                        class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton :disabled="form.processing">Submit my vote</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
