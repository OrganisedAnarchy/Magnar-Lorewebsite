<script setup>
import { computed, reactive, ref } from 'vue';
// Phonetics (IPA picker, sound playback, speech) is disabled for now: phonetics coming soon.
// import IpaPicker from './ipa/IpaPicker.vue';
// import { playIpa, playSequence } from './ipa/audio.js';
// import { speakIpa, warmSpeech } from './ipa/espeak.js';
// import { playSynth } from './ipa/synth.js';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    csrf: { type: String, required: true },
    initial: { type: Object, required: true },
    swadesh: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    cancelUrl: { type: String, required: true },
});

const form = reactive({
    name: props.initial.name ?? '',
    description: props.initial.description ?? '',
    phonology_notes: props.initial.phonology_notes ?? '',
    grammar: props.initial.grammar ?? '',
    writing_system: props.initial.writing_system ?? '',
});

// const phonemes = ref([...(props.initial.phonemes ?? [])]);
// Syllables are entered as romanized text; any IPA saved earlier is carried along untouched.
const syllables = ref((props.initial.syllables ?? []).map((item) => (
    typeof item === 'string' ? { ipa: '', romanized: item } : { ipa: item.ipa ?? '', romanized: item.romanized ?? '' }
)));
const draftRomanized = ref('');
const words = ref((props.initial.words ?? []).map((row) => ({
    word: row.word ?? '',
    meaning: row.meaning ?? '',
    notes: row.notes ?? '',
})));

const generator = reactive({ count: 40, maxSyllables: 3 });
const generated = ref([]);
const showMissing = ref(false);

const error = (name) => props.errors[name]?.[0];
const nestedErrors = (prefix) => Object.entries(props.errors)
    .filter(([key]) => key.startsWith(`${prefix}.`))
    .map(([, messages]) => messages[0]);

const canAddSyllable = computed(() => draftRomanized.value.trim() !== '');
const romanizedSyllables = computed(() => [...new Set(syllables.value.map((item) => item.romanized.trim()).filter(Boolean))]);

const addSyllable = () => {
    if (!canAddSyllable.value) {
        return;
    }
    const romanized = draftRomanized.value.trim();
    if (!syllables.value.some((item) => item.romanized.trim() === romanized)) {
        syllables.value.push({ ipa: '', romanized });
    }
    draftRomanized.value = '';
};

const removeSyllable = (index) => syllables.value.splice(index, 1);

const randomItem = (items) => items[Math.floor(Math.random() * items.length)];

const generateText = () => {
    if (!romanizedSyllables.value.length) {
        generated.value = [];
        return;
    }
    const count = Math.min(Math.max(Number(generator.count) || 1, 1), 500);
    const max = Math.min(Math.max(Number(generator.maxSyllables) || 1, 1), 8);
    generated.value = Array.from({ length: count }, () => {
        const length = 1 + Math.floor(Math.random() * max);
        return Array.from({ length }, () => randomItem(romanizedSyllables.value)).join('');
    });
};

const lexiconHas = (word) => words.value.some((row) => row.word.trim() === word);

const addGeneratedWord = (word) => {
    if (!lexiconHas(word)) {
        words.value.push({ word, meaning: '', notes: '' });
    }
};

const addBlankWord = (meaning = '') => words.value.push({ word: '', meaning, notes: '' });
const removeWord = (index) => words.value.splice(index, 1);

const savedWords = computed(() => words.value.filter((row) => row.word.trim() !== ''));

const stripGloss = (text) => text.replace(/\s*\(.*?\)\s*/g, ' ').replace(/^to\s+/, '').trim();

const swadeshIndex = computed(() => {
    const exact = new Map();
    const loose = new Map();
    props.swadesh.forEach((item, index) => {
        const full = item.toLowerCase();
        exact.set(full, index);
        const stripped = stripGloss(full);
        loose.set(stripped, loose.has(stripped) ? null : index);
    });
    return { exact, loose };
});

const coveredIndexes = computed(() => {
    const covered = new Set();
    savedWords.value.forEach((row) => {
        const meaning = row.meaning.trim().toLowerCase();
        if (!meaning) {
            return;
        }
        const index = swadeshIndex.value.exact.get(meaning) ?? swadeshIndex.value.loose.get(stripGloss(meaning));
        if (index !== undefined && index !== null) {
            covered.add(index);
        }
    });
    return covered;
});

const missingSwadesh = computed(() => props.swadesh.filter((_, index) => !coveredIndexes.value.has(index)));
const coveragePercent = computed(() => (props.swadesh.length ? Math.round((coveredIndexes.value.size / props.swadesh.length) * 100) : 0));
</script>

<template>
    <form method="POST" class="language-form" :action="action">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <section class="panel">
            <h2>Overview</h2>
            <label for="name">Name</label>
            <input id="name" v-model="form.name" name="name" required>
            <div v-if="error('name')" class="error">{{ error('name') }}</div>

            <label for="description">Description</label>
            <textarea id="description" v-model="form.description" name="description" class="short" />
            <div v-if="error('description')" class="error">{{ error('description') }}</div>
        </section>

        <!-- Phonetics coming soon. Previous IPA phonology and IPA syllable builder removed from the UI for now.
        <section class="panel">
            <h2>Phonology (IPA)</h2>
            <IpaPicker v-model="phonemes" name="phonemes" :errors="nestedErrors('phonemes')" />

            <label for="phonology_notes">Phonology notes</label>
            <textarea id="phonology_notes" v-model="form.phonology_notes" name="phonology_notes" class="short" placeholder="Allophony, stress, tone, phonotactics…" />
        </section>
        -->

        <section class="panel">
            <h2>Phonology</h2>
            <p class="hint">Phonetics coming soon.</p>
        </section>

        <section class="panel">
            <h2>Syllables</h2>
            <p class="hint">Enter each syllable as written, e.g. <em>Le</em> or <em>Mon</em>.</p>

            <div class="field-row">
                <input id="draft-romanized" v-model="draftRomanized" maxlength="20" autocomplete="off" aria-label="New syllable" placeholder="e.g. Le" @keydown.enter.prevent="addSyllable">
                <button type="button" class="btn small" :disabled="!canAddSyllable" @click="addSyllable">Add syllable</button>
            </div>

            <p class="hint">{{ syllables.length }} syllable{{ syllables.length === 1 ? '' : 's' }}</p>
            <div v-if="syllables.length" class="syllable-list">
                <div v-for="(item, index) in syllables" :key="index" class="syllable-row">
                    <input v-model="item.romanized" maxlength="20" aria-label="Syllable">
                    <button type="button" class="btn btn-danger small" aria-label="Remove syllable" @click="removeSyllable(index)">✕</button>
                </div>
            </div>

            <template v-for="(item, index) in syllables" :key="`syllable-${index}`">
                <template v-if="item.romanized.trim()">
                    <input type="hidden" :name="`syllables[${index}][ipa]`" :value="item.ipa">
                    <input type="hidden" :name="`syllables[${index}][romanized]`" :value="item.romanized.trim()">
                </template>
            </template>
            <div v-for="message in nestedErrors('syllables')" :key="message" class="error">{{ message }}</div>
        </section>

        <section class="panel">
            <h2>Word generator</h2>
            <p class="hint">Builds sample words from your syllables. Click a word to add it to the lexicon.</p>
            <div class="generator-controls">
                <label class="inline-field">Words
                    <input v-model.number="generator.count" type="number" min="1" max="500">
                </label>
                <label class="inline-field">Max syllables per word
                    <input v-model.number="generator.maxSyllables" type="number" min="1" max="8">
                </label>
                <button type="button" class="btn small" :disabled="!romanizedSyllables.length" @click="generateText">Generate</button>
            </div>
            <p v-if="!romanizedSyllables.length" class="hint">Add some syllables first.</p>
            <div v-if="generated.length" class="word-chips">
                <button
                    v-for="(word, index) in generated"
                    :key="`${index}-${word}`"
                    type="button"
                    class="word-chip"
                    :class="{ added: lexiconHas(word) }"
                    :disabled="lexiconHas(word)"
                    :title="lexiconHas(word) ? 'Already in lexicon' : 'Add to lexicon'"
                    @click="addGeneratedWord(word)"
                >{{ word }}</button>
            </div>
        </section>

        <section class="panel">
            <h2>Lexicon</h2>
            <div class="coverage" aria-live="polite">
                <div class="coverage-text">
                    Swadesh 207: <strong>{{ coveredIndexes.size }} / {{ swadesh.length }}</strong> ({{ coveragePercent }}%)
                </div>
                <div class="coverage-bar"><div class="coverage-fill" :style="{ width: coveragePercent + '%' }" /></div>
                <button type="button" class="link-button" @click="showMissing = !showMissing">
                    {{ showMissing ? 'Hide' : 'Show' }} missing ({{ missingSwadesh.length }})
                </button>
                <div v-if="showMissing" class="word-chips">
                    <button v-for="item in missingSwadesh" :key="item" type="button" class="word-chip" title="Add an entry with this meaning" @click="addBlankWord(item)">{{ item }}</button>
                </div>
            </div>

            <datalist id="swadesh-options">
                <option v-for="item in swadesh" :key="item" :value="item" />
            </datalist>

            <div class="lexicon-rows">
                <div v-for="(row, index) in words" :key="index" class="lexicon-row">
                    <input v-model="row.word" placeholder="Word" aria-label="Word">
                    <input v-model="row.meaning" list="swadesh-options" placeholder="Meaning" aria-label="Meaning">
                    <input v-model="row.notes" placeholder="Notes" aria-label="Notes">
                    <button type="button" class="btn btn-danger small" aria-label="Remove word" @click="removeWord(index)">✕</button>
                </div>
            </div>
            <button type="button" class="btn small" @click="addBlankWord()">Add word</button>

            <template v-for="(row, index) in savedWords" :key="`saved-${index}`">
                <input type="hidden" :name="`words[${index}][word]`" :value="row.word.trim()">
                <input type="hidden" :name="`words[${index}][meaning]`" :value="row.meaning.trim()">
                <input type="hidden" :name="`words[${index}][notes]`" :value="row.notes.trim()">
            </template>
            <div v-for="message in nestedErrors('words')" :key="message" class="error">{{ message }}</div>
        </section>

        <section class="panel">
            <h2>Grammar</h2>
            <textarea id="grammar" v-model="form.grammar" name="grammar" aria-label="Grammar" placeholder="Word order, cases, tenses, agreement…" />
            <div v-if="error('grammar')" class="error">{{ error('grammar') }}</div>
        </section>

        <section class="panel">
            <h2>Writing system</h2>
            <textarea id="writing_system" v-model="form.writing_system" name="writing_system" aria-label="Writing system" placeholder="Script, direction, letters, punctuation…" />
            <div v-if="error('writing_system')" class="error">{{ error('writing_system') }}</div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">Save</button>
            <a :href="cancelUrl">Cancel</a>
        </div>
    </form>
</template>
