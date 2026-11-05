<script setup>
defineProps({
    state: { type: String, default: 'idle' },
    savedAt: { type: String, default: null },
});

const formatTime = (value) => (value ? new Date(value).toLocaleString() : '');
</script>

<template>
    <span class="hint draft-status" aria-live="polite">
        <template v-if="state === 'pending'">Draft changes…</template>
        <template v-else-if="state === 'saved'">Draft autosaved {{ formatTime(savedAt) }}</template>
        <template v-else-if="state === 'too-large'">Draft too large to autosave — save your work.</template>
    </span>
</template>
