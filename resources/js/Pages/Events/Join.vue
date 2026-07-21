<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageTitle from '@/Components/PageTitle.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    code: {
        type: String,
        default: null,
    },
});

const form = useForm({
    invite_code: props.code ?? '',
});

const submit = () =>
    form
        .transform((data) => ({
            invite_code: data.invite_code.trim().toUpperCase(),
        }))
        .post(route('events.join.store'));
</script>

<template>
    <Head title="Join an event" />

    <AuthenticatedLayout>
        <PageTitle title="Join an event" />

        <div class="card">
            <p class="text-sm font-semibold text-ink-muted dark:text-gray-400">
                Enter the invite code the host shared with you.
            </p>
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                        <div>
                            <InputLabel for="invite_code" value="Invite code" />
                            <TextInput
                                id="invite_code"
                                v-model="form.invite_code"
                                type="text"
                                class="mt-1 block w-full font-mono uppercase tracking-widest"
                                placeholder="K3F9QZ7A"
                                autofocus
                            />
                            <InputError :message="form.errors.invite_code" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <Link
                                :href="route('events.index')"
                                class="font-display text-sm font-semibold text-grape-600 underline decoration-2 underline-offset-2 dark:text-grape-300"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">Join</PrimaryButton>
                        </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
