<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    // Where the banner sits, so it can clear the tab bar in-app.
    bottomOffset: { type: String, default: '1rem' },
});

const DISMISS_KEY = 'eat-install-dismissed';

const visible = ref(false);
const isIos = ref(false);
let deferredPrompt = null;

const isStandalone = () =>
    window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true;

const wasDismissed = () => {
    try {
        return localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        return false;
    }
};

const dismiss = () => {
    visible.value = false;
    try {
        localStorage.setItem(DISMISS_KEY, '1');
    } catch {
        /* storage unavailable — banner just won't be remembered */
    }
};

const install = async () => {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    visible.value = false;
};

const onBeforeInstallPrompt = (event) => {
    event.preventDefault();
    deferredPrompt = event;
    if (!wasDismissed()) visible.value = true;
};

onMounted(() => {
    if (isStandalone() || wasDismissed()) return;

    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.addEventListener('appinstalled', dismiss);

    // iOS Safari never fires beforeinstallprompt — nudge with manual steps.
    const ua = window.navigator.userAgent;
    isIos.value = /iphone|ipad|ipod/i.test(ua) && !/crios|fxios|edgios/i.test(ua);
    if (isIos.value) visible.value = true;
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt);
    window.removeEventListener('appinstalled', dismiss);
});
</script>

<template>
    <Transition name="pref-pop">
        <div
            v-if="visible"
            class="fixed inset-x-3 z-50 mx-auto flex max-w-md items-center gap-3 rounded-blob border-3 border-ink bg-white px-4 py-3 shadow-cartoon-lg dark:bg-ink-800"
            :style="{ bottom: bottomOffset }"
            role="dialog"
            aria-label="Install eat"
        >
            <span class="text-3xl">📲</span>
            <div class="min-w-0 flex-1">
                <p class="font-display font-bold text-ink dark:text-cream">Install eat</p>
                <p class="text-xs font-semibold text-ink-muted dark:text-gray-300">
                    <template v-if="isIos">
                        Tap <span class="font-bold">Share ⬆️</span> then
                        <span class="font-bold">“Add to Home Screen”</span>.
                    </template>
                    <template v-else>
                        One tap from your home screen — no app store.
                    </template>
                </p>
            </div>
            <button
                v-if="!isIos"
                type="button"
                class="btn btn-primary shrink-0 px-3 py-2"
                @click="install"
            >
                Install
            </button>
            <button
                type="button"
                class="shrink-0 rounded-full border-3 border-ink px-2 py-0.5 font-display text-sm font-bold text-ink dark:text-cream"
                aria-label="Dismiss"
                @click="dismiss"
            >
                ✕
            </button>
        </div>
    </Transition>
</template>
