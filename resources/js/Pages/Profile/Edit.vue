<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-ink dark:text-cream">
                Profile
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-5 px-4 sm:px-6">
                <!-- Account summary -->
                <div class="card flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full border-3 border-ink bg-sunny-300 font-display text-lg font-bold text-ink">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                        <div>
                            <p class="font-display font-bold text-ink dark:text-cream">{{ user.name }}</p>
                            <p class="text-sm font-semibold text-ink-muted dark:text-gray-400">{{ user.email }}</p>
                        </div>
                    </div>
                    <Link :href="route('logout')" method="post" as="button" class="btn btn-ghost">
                        Log out
                    </Link>
                </div>

                <div class="card">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <div class="card">
                    <UpdatePasswordForm />
                </div>

                <div class="card">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
