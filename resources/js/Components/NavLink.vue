<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-4 border-punch-500 font-display text-sm font-semibold leading-5 text-ink dark:text-cream focus:outline-none transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-4 border-transparent font-display text-sm font-semibold leading-5 text-ink-muted dark:text-gray-400 hover:text-ink hover:border-sunny-300 dark:hover:text-cream focus:outline-none transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
