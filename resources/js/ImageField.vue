<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    label: { type: String, default: 'Image' },
    currentUrl: { type: String, default: null },
    error: { type: String, default: null },
});

const input = ref(null);
const preview = ref(null);
const remove = ref(false);

const shown = computed(() => preview.value ?? (remove.value ? null : props.currentUrl));

const clearPreview = () => {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
        preview.value = null;
    }
};

const onChosen = (event) => {
    clearPreview();
    const file = event.target.files[0];
    preview.value = file ? URL.createObjectURL(file) : null;
    if (file) {
        remove.value = false;
    }
};

// A save replaces the current image, so the chosen file and removal tick no longer apply.
watch(() => props.currentUrl, () => {
    clearPreview();
    remove.value = false;
    if (input.value) {
        input.value.value = '';
    }
});

onBeforeUnmount(clearPreview);
</script>

<template>
    <div class="image-field">
        <label for="image">{{ label }}</label>
        <input id="image" ref="input" name="image" type="file" accept="image/png,image/jpeg,image/webp,image/gif" @change="onChosen">
        <p class="hint">Optional. JPG, PNG, WebP or GIF, up to 10 MB.</p>
        <div v-if="error" class="error">{{ error }}</div>
        <label v-if="currentUrl" class="checkbox">
            <input v-model="remove" type="checkbox" name="remove_image" value="1">
            Remove the current {{ label.toLowerCase() }}
        </label>
        <img v-if="shown" class="image-preview" :src="shown" :alt="`${label} preview`">
    </div>
</template>
