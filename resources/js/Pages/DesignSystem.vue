<script setup>
import { Head } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PreferenceTile from '@/Components/PreferenceTile.vue';
import { reactive, ref } from 'vue';

// Brand scales exposed as Tailwind utilities (see tailwind.config.js).
const palettes = [
    { name: 'punch', role: 'Primary action', shades: [300, 400, 500, 600] },
    { name: 'sunny', role: 'Highlight', shades: [200, 300, 400, 500] },
    { name: 'mint', role: 'Success / calm', shades: [300, 400, 500, 600] },
    { name: 'grape', role: 'Links / focus', shades: [300, 400, 500, 600] },
    { name: 'berry', role: 'Danger / love', shades: [300, 400, 500, 600] },
    { name: 'sky', role: 'Info', shades: [300, 400, 500, 600] },
];

const shadows = ['cartoon-xs', 'cartoon-sm', 'cartoon', 'cartoon-lg', 'cartoon-xl'];

const sample = ref('Team dinner');
const checked = ref(true);

// Toggleable chips (multi-select), mirrors the vote criteria picker.
const chipOptions = ['Cheap', 'Veggie', 'Spicy', 'Cozy', 'Fancy'];
const selectedChips = ref(['Veggie', 'Cozy']);
const toggleChip = (opt) => {
    const i = selectedChips.value.indexOf(opt);
    i === -1 ? selectedChips.value.push(opt) : selectedChips.value.splice(i, 1);
};

const sectionTitle =
    'mb-4 font-display text-sm font-bold uppercase tracking-widest text-ink-muted dark:text-gray-400';

// Three-state preference tiles (neutral → want → avoid), the vote's core.
const prefOptions = ['Italian', 'Thai', 'Sushi', '€€', 'Spicy', 'Vegan'];
const prefs = reactive({ Thai: 'want', Spicy: 'avoid' });
const setPref = (opt, value) => {
    if (value === 'neutral') delete prefs[opt];
    else prefs[opt] = value;
};
</script>

<template>
    <Head title="Design system" />

    <div class="min-h-screen bg-cream dark:bg-ink-900">
        <!-- Hero -->
        <header class="border-b-3 border-ink bg-punch-400">
            <div class="mx-auto max-w-5xl px-6 py-14">
                <span class="badge badge-host animate-wiggle">🍴 eat</span>
                <h1 class="mt-4 font-display text-4xl font-bold text-white sm:text-5xl">
                    Cartoon design system
                </h1>
                <p class="mt-3 max-w-xl font-display text-lg font-medium text-white/90">
                    Vivid, playful, and chunky — thick ink outlines, hard sticker
                    shadows, and bouncy interactions.
                </p>
            </div>
        </header>

        <div class="mx-auto max-w-5xl space-y-14 px-6 py-14">
            <!-- Typography -->
            <section>
                <h2 :class="sectionTitle">Typography</h2>
                <div class="card space-y-3">
                    <p class="font-display text-3xl font-bold text-ink dark:text-cream">
                        Fredoka — display / headings
                    </p>
                    <p class="text-lg text-ink dark:text-cream">
                        Nunito — body copy. Friendly, rounded and legible for
                        everything that isn't a headline.
                    </p>
                    <p class="font-mono text-sm text-ink-muted dark:text-gray-400">
                        Mono — invite codes &amp; data · K3F9QZ7A
                    </p>
                </div>
            </section>

            <!-- Colours -->
            <section>
                <h2 :class="sectionTitle">Colour</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="p in palettes" :key="p.name" class="panel">
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-display text-lg font-bold capitalize text-ink dark:text-cream">
                                {{ p.name }}
                            </h3>
                            <span class="text-xs font-semibold text-ink-muted dark:text-gray-400">
                                {{ p.role }}
                            </span>
                        </div>
                        <div class="mt-3 flex overflow-hidden rounded-xl2 border-3 border-ink">
                            <div
                                v-for="s in p.shades"
                                :key="s"
                                class="h-12 flex-1"
                                :class="`bg-${p.name}-${s}`"
                            />
                        </div>
                    </div>
                    <!-- Neutrals -->
                    <div class="panel">
                        <h3 class="font-display text-lg font-bold text-ink dark:text-cream">
                            Neutrals
                        </h3>
                        <div class="mt-3 flex overflow-hidden rounded-xl2 border-3 border-ink">
                            <div class="h-12 flex-1 bg-ink" />
                            <div class="h-12 flex-1 bg-ink-muted" />
                            <div class="h-12 flex-1 bg-cream-200" />
                            <div class="h-12 flex-1 bg-white" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Buttons -->
            <section>
                <h2 :class="sectionTitle">Buttons</h2>
                <div class="card space-y-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <PrimaryButton>Create event</PrimaryButton>
                        <SecondaryButton>Join with code</SecondaryButton>
                        <DangerButton>Delete</DangerButton>
                        <button class="btn btn-mint">Success</button>
                        <button class="btn btn-ghost">Ghost</button>
                        <PrimaryButton disabled>Disabled</PrimaryButton>
                    </div>
                    <p class="text-sm font-semibold text-ink-muted dark:text-gray-400">
                        Hover to see the lift, click to press them down.
                    </p>
                </div>
            </section>

            <!-- Form controls -->
            <section>
                <h2 :class="sectionTitle">Form controls</h2>
                <div class="card grid gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="ds-name" value="Event name" />
                        <TextInput id="ds-name" v-model="sample" class="w-full" />
                    </div>
                    <div>
                        <InputLabel for="ds-meal" value="Meal" />
                        <select id="ds-meal" class="input">
                            <option>Breakfast 🥐</option>
                            <option>Lunch 🥪</option>
                            <option selected>Dinner 🍝</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-3 sm:col-span-2">
                        <Checkbox :checked="checked" @update:checked="checked = $event" />
                        <span class="font-semibold text-ink dark:text-cream">
                            Send me a reminder
                        </span>
                    </label>
                </div>
            </section>

            <!-- Badges -->
            <section>
                <h2 :class="sectionTitle">Badges</h2>
                <div class="card flex flex-wrap gap-3">
                    <span class="badge badge-host">Host</span>
                    <span class="badge badge-mint">Going</span>
                    <span class="badge badge-grape">Maybe</span>
                    <span class="badge badge-sky">Invited</span>
                </div>
            </section>

            <!-- Chips -->
            <section>
                <h2 :class="sectionTitle">Chips</h2>
                <div class="card space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="opt in chipOptions"
                            :key="opt"
                            type="button"
                            class="chip"
                            :class="{ 'chip-selected': selectedChips.includes(opt) }"
                            @click="toggleChip(opt)"
                        >
                            {{ opt }}
                        </button>
                    </div>
                    <p class="text-sm font-semibold text-ink-muted dark:text-gray-400">
                        Toggleable multi-select pills — click to pick. Used for the
                        vote criteria picker.
                    </p>
                </div>
            </section>

            <!-- Preference tiles -->
            <section>
                <h2 :class="sectionTitle">Preference tiles</h2>
                <div class="card space-y-4">
                    <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-6">
                        <PreferenceTile
                            v-for="opt in prefOptions"
                            :key="opt"
                            :label="opt"
                            :model-value="prefs[opt] ?? 'neutral'"
                            @update:model-value="(v) => setPref(opt, v)"
                        />
                    </div>
                    <p class="text-sm font-semibold text-ink-muted dark:text-gray-400">
                        Square, three-state buttons — tap to cycle neutral →
                        <span class="font-bold text-mint-600">want 🟢</span> →
                        <span class="font-bold text-berry-600">avoid 🔴</span>. Powers
                        the immersive vote; every ballot is a wall of these.
                    </p>
                </div>
            </section>

            <!-- Shadows -->
            <section>
                <h2 :class="sectionTitle">Sticker shadows</h2>
                <div class="flex flex-wrap gap-8 rounded-blob border-3 border-ink bg-white p-8 dark:bg-ink-800">
                    <div
                        v-for="s in shadows"
                        :key="s"
                        class="flex h-20 w-20 items-center justify-center rounded-xl2 border-3 border-ink bg-sunny-300 text-xs font-bold text-ink"
                        :class="`shadow-${s}`"
                    >
                        {{ s.replace('cartoon', '') || 'base' }}
                    </div>
                </div>
            </section>

            <!-- Motion -->
            <section>
                <h2 :class="sectionTitle">Motion</h2>
                <div class="card flex flex-wrap items-center gap-10">
                    <span class="animate-float text-4xl">🎈</span>
                    <span class="animate-wiggle text-4xl">🎉</span>
                    <span class="animate-pop-in text-4xl">✨</span>
                </div>
            </section>
        </div>
    </div>
</template>
