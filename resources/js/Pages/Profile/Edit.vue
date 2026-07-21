<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageTitle from '@/Components/PageTitle.vue';
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
        <PageTitle title="Profile" />

        <div class="space-y-4">
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

            <!-- Food preferences -->
            <Link
                :href="route('preferences.edit')"
                class="card-interactive flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border-3 border-ink bg-mint-300 text-2xl">
                        😋
                    </span>
                    <div>
                        <p class="font-display font-bold text-ink dark:text-cream">Food preferences</p>
                        <p class="text-sm font-semibold text-ink-muted dark:text-gray-400">
                            Set what you love and never — we'll pre-fill your votes.
                        </p>
                    </div>
                </div>
                <span class="font-display text-xl font-bold text-ink-muted dark:text-gray-400" aria-hidden="true">→</span>
            </Link>

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
    </AuthenticatedLayout>
</template>
