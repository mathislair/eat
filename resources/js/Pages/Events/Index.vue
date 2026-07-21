<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageTitle from '@/Components/PageTitle.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
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

// Opening an event drops you straight into its phase: swipe once it's closed,
// otherwise vote.
const openHref = (event) =>
    event.status === 'closed'
        ? route('events.reveal', event.id)
        : route('events.vote.edit', event.id);
</script>

<template>
    <Head title="Events" />

    <AuthenticatedLayout>
        <PageTitle title="Your events">
            <Link :href="route('events.create')" class="shrink-0">
                <PrimaryButton>+ New</PrimaryButton>
            </Link>
        </PageTitle>

        <div
            v-if="events.length === 0"
            class="card text-center"
        >
            <p class="text-4xl">🍽️</p>
            <p class="mt-3 font-display text-lg font-semibold text-ink dark:text-cream">
                You have no events yet.
            </p>
            <Link :href="route('events.create')" class="mt-5 inline-block">
                <PrimaryButton>Create your first event</PrimaryButton>
            </Link>
        </div>

        <div v-else class="space-y-4">
            <Link
                v-for="event in events"
                :key="event.id"
                :href="openHref(event)"
                class="card card-interactive block"
            >
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                        {{ event.name }}
                    </h3>
                    <span class="text-3xl" :title="event.meal_label">
                        {{ mealEmoji[event.meal] }}
                    </span>
                </div>
                <p class="mt-2 text-sm font-semibold text-ink-muted dark:text-gray-400">
                    {{ formatDate(event.date) }} · {{ event.meal_label }}
                </p>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="font-semibold text-ink-muted dark:text-gray-400">
                        {{ event.attendees_count }}
                        {{ event.attendees_count === 1 ? 'guest' : 'guests' }}
                    </span>
                    <span v-if="event.is_creator" class="badge badge-host">
                        Host
                    </span>
                </div>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
