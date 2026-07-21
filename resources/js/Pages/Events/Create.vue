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
            <h2 class="min-w-0 flex-1 truncate text-lg font-bold leading-tight text-ink dark:text-cream">
                New event
            </h2>
        </template>

        <div class="card">
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
                                class="input"
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
                                class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Create event
                            </PrimaryButton>
                        </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
