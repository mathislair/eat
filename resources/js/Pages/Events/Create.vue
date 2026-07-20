<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    meals: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    date: '',
    meal: 'dinner',
});

const submit = () => form.post(route('events.store'));
</script>

<template>
    <Head title="New event" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                New event
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Team dinner"
                                autofocus
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="date" value="Date" />
                            <TextInput
                                id="date"
                                v-model="form.date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.date" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="meal" value="Meal" />
                            <select
                                id="meal"
                                v-model="form.meal"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option
                                    v-for="meal in meals"
                                    :key="meal.value"
                                    :value="meal.value"
                                >
                                    {{ meal.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.meal" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link
                                :href="route('events.index')"
                                class="text-sm text-gray-600 underline dark:text-gray-400"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Create event
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
