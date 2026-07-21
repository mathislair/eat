<script setup>
/**
 * In-page segmented tab switcher. Splits one dense screen into a couple of
 * focused views without a page load. Drives which panel shows via v-model.
 *
 * Each tab: { key, label, icon?, count? } — `icon` is an emoji/glyph, `count`
 * a small trailing number (e.g. how many guests).
 */
defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    tabs: {
        type: Array,
        required: true,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="tabs" role="tablist">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            :aria-selected="modelValue === tab.key"
            class="tab"
            :class="{ 'tab--active': modelValue === tab.key }"
            @click="$emit('update:modelValue', tab.key)"
        >
            <span v-if="tab.icon" class="text-base leading-none" aria-hidden="true">
                {{ tab.icon }}
            </span>
            <span>{{ tab.label }}</span>
            <span v-if="tab.count != null" class="tab__count">{{ tab.count }}</span>
        </button>
    </div>
</template>
