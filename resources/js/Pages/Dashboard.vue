<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-ink dark:text-cream">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="card">
                    <p class="font-display text-lg font-semibold text-ink dark:text-cream">
                        You're logged in! 🎉
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
