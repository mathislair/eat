<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Join an event
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-md sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
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
                                class="text-sm text-gray-600 underline dark:text-gray-400"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">Join</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
