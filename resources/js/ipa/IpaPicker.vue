<script setup>
/**
 * Reusable IPA sound picker.
 *
 *   <IpaPicker v-model="phonemes" name="phonemes" />
 *
 * v-model is an array of IPA symbols. When `name` is set, one hidden
 * `name[]` input is rendered per sound so it posts with a normal form.
 * Clicking a key toggles it and plays the sound (disable with :play-sounds="false").
 * Recordings are served from `audio-url` (default /audio/ipa/, i.e. public/audio/ipa).
 * Styles (.tabs, .ipa-key, …) live in the admin layout stylesheet.
 */
import { computed, onMounted, ref, watch } from 'vue';
import { ipaCategories, knownSymbols } from './data.js';
import { playIpa, preloadRecordings } from './audio.js';

const model = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    name: { type: String, default: 'phonemes' },
    playSounds: { type: Boolean, default: true },
    audioUrl: { type: String, default: '/audio/ipa/' },
    errors: { type: Array, default: () => [] },
});

const activeCategory = ref(ipaCategories[0].key);
const customSymbol = ref('');

const currentCategory = computed(() => ipaCategories.find((category) => category.key === activeCategory.value));
const customSymbols = computed(() => model.value.filter((symbol) => !knownSymbols.has(symbol)));
const selectedCount = (category) => category.groups
    .flatMap((group) => group.symbols)
    .filter((symbol) => model.value.includes(symbol)).length;

const play = (symbol) => {
    if (props.playSounds) {
        playIpa(symbol, props.audioUrl);
    }
};

const warmCategory = () => {
    if (props.playSounds) {
        preloadRecordings(currentCategory.value.groups.flatMap((group) => group.symbols), props.audioUrl);
    }
};

onMounted(warmCategory);
watch(activeCategory, warmCategory);

const toggleSymbol = (symbol) => {
    const selected = model.value.includes(symbol);
    model.value = selected ? model.value.filter((existing) => existing !== symbol) : [...model.value, symbol];
    if (!selected) {
        play(symbol);
    }
};

const addCustomSymbol = () => {
    const symbol = customSymbol.value.trim();
    if (symbol && !model.value.includes(symbol)) {
        model.value = [...model.value, symbol];
        play(symbol);
    }
    customSymbol.value = '';
};
</script>

<template>
    <div class="ipa-picker">
        <p class="hint">Pick the sounds this language uses. {{ model.length }} selected.<template v-if="playSounds"> Clicking a sound plays a recording of it (Wikimedia Commons, CC BY-SA).</template></p>

        <div class="tabs" role="tablist">
            <button
                v-for="category in ipaCategories"
                :key="category.key"
                type="button"
                role="tab"
                class="tab"
                :class="{ active: activeCategory === category.key }"
                :aria-selected="activeCategory === category.key"
                @click="activeCategory = category.key"
            >
                {{ category.label }}
                <span v-if="selectedCount(category)" class="badge">{{ selectedCount(category) }}</span>
            </button>
        </div>

        <div v-for="group in currentCategory.groups" :key="group.label" class="ipa-group">
            <div class="ipa-group-label">{{ group.label }}</div>
            <div class="ipa-keys">
                <button
                    v-for="symbol in group.symbols"
                    :key="symbol"
                    type="button"
                    class="ipa-key"
                    :class="{ selected: model.includes(symbol) }"
                    :aria-pressed="model.includes(symbol)"
                    @click="toggleSymbol(symbol)"
                >{{ symbol }}</button>
            </div>
        </div>

        <div class="ipa-group">
            <div class="ipa-group-label">Custom symbols</div>
            <div class="ipa-keys">
                <button v-for="symbol in customSymbols" :key="symbol" type="button" class="ipa-key selected" title="Remove" @click="toggleSymbol(symbol)">{{ symbol }}</button>
                <input v-model="customSymbol" class="ipa-custom" placeholder="Add symbol" maxlength="20" @keydown.enter.prevent="addCustomSymbol">
                <button type="button" class="btn small" @click="addCustomSymbol">Add</button>
            </div>
        </div>

        <div class="phoneme-summary" aria-live="polite">
            <strong>Inventory:</strong>
            <span v-if="model.length">/{{ model.join(' ') }}/</span>
            <span v-else class="hint">nothing selected yet</span>
        </div>

        <template v-if="name">
            <input v-for="symbol in model" :key="symbol" type="hidden" :name="`${name}[]`" :value="symbol">
        </template>
        <div v-for="message in errors" :key="message" class="error">{{ message }}</div>
    </div>
</template>
