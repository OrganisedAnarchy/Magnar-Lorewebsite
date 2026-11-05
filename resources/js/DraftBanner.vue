<script setup>
defineProps({
    restoredAt: { type: String, default: null },
});

defineEmits(['discard']);

const formatTime = (value) => new Date(value).toLocaleString();
</script>

<template>
    <div v-if="restoredAt" class="draft-banner" role="status">
        Restored an unsaved draft from {{ formatTime(restoredAt) }}.
        <button type="button" class="link-button" @click="$emit('discard')">Discard draft</button>
    </div>
</template>
