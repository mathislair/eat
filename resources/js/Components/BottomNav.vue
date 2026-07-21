<script setup>
import { Link } from '@inertiajs/vue3';

// Icons are single-path, 24×24, stroked with currentColor.
const tabs = [
    {
        key: 'events',
        label: 'Events',
        href: route('events.index'),
        match: ['events.index', 'events.show', 'events.hub', 'events.vote.*', 'events.reveal'],
        icon: 'M3 10.5 12 3l9 7.5M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5',
    },
    {
        key: 'create',
        label: 'Create',
        href: route('events.create'),
        match: ['events.create', 'events.store'],
        icon: 'M12 8v8M8 12h8M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
    },
    {
        key: 'join',
        label: 'Join',
        href: route('events.join'),
        match: ['events.join', 'events.join.store'],
        icon: 'M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 1 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 1 0 7.07 7.07l1.71-1.71',
    },
    {
        key: 'profile',
        label: 'Profile',
        href: route('profile.edit'),
        match: ['profile.edit', 'preferences.edit'],
        icon: 'M20 21v-1a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
    },
];

const isActive = (matches) => matches.some((pattern) => route().current(pattern));
</script>

<template>
    <nav class="bottom-nav" aria-label="Primary">
        <Link
            v-for="tab in tabs"
            :key="tab.key"
            :href="tab.href"
            class="bottom-nav__item"
            :class="{ 'bottom-nav__item--active': isActive(tab.match) }"
            :aria-current="isActive(tab.match) ? 'page' : undefined"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path :d="tab.icon" />
            </svg>
            <span>{{ tab.label }}</span>
        </Link>
    </nav>
</template>
