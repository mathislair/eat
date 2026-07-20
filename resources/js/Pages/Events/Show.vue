<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const copied = ref(false);

const mealEmoji = {
    breakfast: '🥐',
    lunch: '🥪',
    dinner: '🍝',
};

const formatDate = (iso) =>
    new Date(iso + 'T00:00:00').toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.event.invite_code);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch (e) {
        // Clipboard unavailable (e.g. insecure context) — ignore silently.
    }
};

const destroy = () => {
    if (confirm('Delete this event for everyone? This cannot be undone.')) {
        router.delete(route('events.destroy', props.event.id));
    }
};

const leave = () => {
    if (confirm('Leave this event?')) {
        router.delete(route('events.leave', props.event.id));
    }
};
</script>

<template>
    <Head :title="event.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ event.name }}
                </h2>
                <Link
                    :href="route('events.index')"
                    class="text-sm text-gray-600 underline dark:text-gray-400"
                >
                    Back to events
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <!-- Details -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="flex items-start gap-4">
                        <span class="text-4xl">{{ mealEmoji[event.meal] }}</span>
                        <div>
                            <p class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ formatDate(event.date) }}
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ event.meal_label }} · hosted by {{ event.creator.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Invite -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Invite code
                    </h3>
                    <div class="mt-2 flex items-center gap-3">
                        <code
                            class="rounded bg-gray-100 px-3 py-2 font-mono text-lg tracking-widest text-gray-900 dark:bg-gray-900 dark:text-gray-100"
                        >
                            {{ event.invite_code }}
                        </code>
                        <SecondaryButton type="button" @click="copyCode">
                            {{ copied ? 'Copied!' : 'Copy code' }}
                        </SecondaryButton>
                    </div>
                    <p class="mt-2 break-all text-xs text-gray-500 dark:text-gray-400">
                        Share link: {{ event.join_url }}
                    </p>
                </div>

                <!-- Attendees -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Guests ({{ event.attendees.length }})
                    </h3>
                    <ul class="mt-3 divide-y divide-gray-100 dark:divide-gray-700">
                        <li
                            v-for="attendee in event.attendees"
                            :key="attendee.id"
                            class="flex items-center justify-between py-2"
                        >
                            <span class="text-gray-900 dark:text-gray-100">
                                {{ attendee.name }}
                            </span>
                            <span
                                v-if="attendee.id === event.creator.id"
                                class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200"
                            >
                                Host
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="flex justify-end">
                    <DangerButton v-if="event.is_creator" @click="destroy">
                        Delete event
                    </DangerButton>
                    <DangerButton v-else @click="leave">
                        Leave event
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
