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
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Vote — {{ event.name }}
                </h2>
                <Link
                    :href="route('events.show', event.id)"
                    class="text-sm text-gray-600 underline dark:text-gray-400"
                >
                    Back to event
                </Link>
            </div>
        </template>

        <div class="py-12">
            <form @submit.prevent="submit" class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <!-- Nationalities -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Nationalities
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
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
                            class="flex cursor-pointer items-center gap-2 rounded px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <input
                                type="checkbox"
                                :checked="form.nationalities.includes(n.id)"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900"
                                @change="toggleNationality(n.id)"
                            />
                            <span class="text-sm text-gray-800 dark:text-gray-200">{{ n.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Criteria -->
                <div
                    v-for="group in criteriaTypes"
                    :key="group.type"
                    class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ group.label }}
                    </h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="option in group.options"
                            :key="option.value"
                            type="button"
                            class="rounded-full border px-3 py-1 text-sm transition"
                            :class="isChecked(group.type, option.value)
                                ? 'border-indigo-500 bg-indigo-600 text-white'
                                : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'"
                            @click="toggleCriterion(group.type, option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('events.show', event.id)"
                        class="text-sm text-gray-600 underline dark:text-gray-400"
                    >
                        Cancel
                    </Link>
                    <PrimaryButton :disabled="form.processing">Submit my vote</PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
