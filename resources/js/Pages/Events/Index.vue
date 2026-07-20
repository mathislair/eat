<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});

const mealEmoji = {
    breakfast: '🥐',
    lunch: '🥪',
    dinner: '🍝',
};

const formatDate = (iso) =>
    new Date(iso + 'T00:00:00').toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
</script>

<template>
    <Head title="Events" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Events
                </h2>
                <div class="flex gap-2">
                    <Link :href="route('events.join')">
                        <SecondaryButton>Join with code</SecondaryButton>
                    </Link>
                    <Link :href="route('events.create')">
                        <PrimaryButton>New event</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    v-if="events.length === 0"
                    class="rounded-lg bg-white p-10 text-center shadow-sm dark:bg-gray-800"
                >
                    <p class="text-gray-600 dark:text-gray-300">
                        You have no events yet.
                    </p>
                    <Link :href="route('events.create')" class="mt-4 inline-block">
                        <PrimaryButton>Create your first event</PrimaryButton>
                    </Link>
                </div>

                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="event in events"
                        :key="event.id"
                        :href="route('events.show', event.id)"
                        class="block rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md dark:bg-gray-800"
                    >
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ event.name }}
                            </h3>
                            <span class="text-2xl" :title="event.meal_label">
                                {{ mealEmoji[event.meal] }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ formatDate(event.date) }} · {{ event.meal_label }}
                        </p>
                        <div class="mt-4 flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ event.attendees_count }}
                                {{ event.attendees_count === 1 ? 'guest' : 'guests' }}
                            </span>
                            <span
                                v-if="event.is_creator"
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200"
                            >
                                Host
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
