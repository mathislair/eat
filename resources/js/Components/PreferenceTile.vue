<script setup>
import { computed } from 'vue';

/**
 * A square, tappable preference button. One tap cycles through three states —
 * neutral → want (🟢) → avoid (🔴) → neutral — so a whole ballot is just a
 * wall of these. The icon and colour always show the current feeling.
 */
const props = defineProps({
    label: { type: String, required: true },
    // 'neutral' | 'want' | 'avoid'
    modelValue: { type: String, default: 'neutral' },
});

const emit = defineEmits(['update:modelValue']);

const NEXT = { neutral: 'want', want: 'avoid', avoid: 'neutral' };

const cycle = () => emit('update:modelValue', NEXT[props.modelValue] ?? 'want');

const hint = computed(
    () =>
        ({
            want: 'I want this',
            avoid: 'No way',
            neutral: 'No preference — tap to choose',
        })[props.modelValue],
);
</script>

<template>
    <button
        type="button"
        class="pref-tile"
        :class="{
            'pref-tile--want': modelValue === 'want',
            'pref-tile--avoid': modelValue === 'avoid',
        }"
        :aria-pressed="modelValue !== 'neutral'"
        :title="hint"
        @click="cycle"
    >
        <Transition name="pref-pop" mode="out-in">
            <span :key="modelValue" class="pref-tile__icon" aria-hidden="true">
                <!-- want: check -->
                <svg
                    v-if="modelValue === 'want'"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M5 13l4 4L19 7" />
                </svg>
                <!-- avoid: cross -->
                <svg
                    v-else-if="modelValue === 'avoid'"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
                <!-- neutral: dash -->
                <svg
                    v-else
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M6 12h12" />
                </svg>
            </span>
        </Transition>
        <span class="pref-tile__label">{{ label }}</span>
        <span class="sr-only">{{ hint }}</span>
    </button>
</template>
