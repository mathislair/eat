<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const page = usePage();

// Look for the first set flash message, in priority order.
const flash = computed(() => page.props.flash ?? {});

const KINDS = {
    success: { classes: 'bg-mint-300 text-ink', emoji: '✅' },
    error: { classes: 'bg-berry-400 text-white', emoji: '⚠️' },
    info: { classes: 'bg-sky-300 text-ink', emoji: '💡' },
};

const current = ref(null);
let timer = null;

function dismiss() {
    current.value = null;
    clearTimeout(timer);
}

watch(
    flash,
    (value) => {
        const kind = Object.keys(KINDS).find((k) => value?.[k]);
        if (!kind) {
            return;
        }
        current.value = { ...KINDS[kind], message: value[kind] };
        clearTimeout(timer);
        timer = setTimeout(dismiss, 4000);
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="current"
            class="pointer-events-none fixed inset-x-0 z-50 flex justify-center px-4"
            style="bottom: calc(6rem + env(safe-area-inset-bottom))"
            role="status"
            aria-live="polite"
        >
            <button
                type="button"
                class="pointer-events-auto flex max-w-md items-center gap-2 rounded-xl2 border-3 px-4 py-3 font-display text-sm font-semibold"
                :class="current.classes"
                style="border-color: var(--ds-line); box-shadow: 4px 4px 0 0 var(--ds-shadow)"
                @click="dismiss"
            >
                <span aria-hidden="true">{{ current.emoji }}</span>
                <span>{{ current.message }}</span>
            </button>
        </div>
    </Transition>
</template>
