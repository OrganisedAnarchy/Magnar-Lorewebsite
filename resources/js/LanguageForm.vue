<script setup>
import { computed, reactive, ref } from 'vue';
import DraftBanner from './DraftBanner.vue';
import DraftStatus from './DraftStatus.vue';
import { useAjaxSubmit } from './useAjaxSubmit.js';
import { useFormDraft } from './useFormDraft.js';
// Phonetics (IPA picker, sound playback, speech) is disabled for now: phonetics coming soon.
// import IpaPicker from './ipa/IpaPicker.vue';
// import { playIpa, playSequence } from './ipa/audio.js';
// import { speakIpa, warmSpeech } from './ipa/espeak.js';
// import { playSynth } from './ipa/synth.js';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    csrf: { type: String, required: true },
    draftKey: { type: String, required: true },
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

const { draftState, restoredAt, savedAt, discardDraft, markSaved } = useFormDraft({
    key: props.draftKey,
    snapshot: () => ({ ...form, syllables: syllables.value, words: words.value }),
    apply: ({ syllables: savedSyllables, words: draftWords, ...values }) => {
        Object.assign(form, values);
        syllables.value = (savedSyllables ?? []).map((item) => ({ ipa: item.ipa ?? '', romanized: item.romanized ?? '' }));
        words.value = (draftWords ?? []).map((row) => ({ word: row.word ?? '', meaning: row.meaning ?? '', notes: row.notes ?? '' }));
    },
    hasErrors: Object.keys(props.errors).length > 0,
});

const generator = reactive({ mode: 'words', count: 40, maxSyllables: 3, paragraphs: 2 });
const generated = ref([]);
const generatedText = ref([]);
const showMissing = ref(false);

const { formAction, formMethod, formErrors, saving, submit } = useAjaxSubmit({
    action: props.action,
    method: props.method,
    errors: props.errors,
    onSaved: (data) => markSaved(data.id),
});

const error = (name) => formErrors.value[name]?.[0];
const nestedErrors = (prefix) => Object.entries(formErrors.value)
    .filter(([key]) => key.startsWith(`${prefix}.`))
    .map(([, messages]) => messages[0]);

const canAddSyllable = computed(() => draftRomanized.value.split(',').some((part) => part.trim() !== ''));
const romanizedSyllables = computed(() => [...new Set(syllables.value.map((item) => item.romanized.trim()).filter(Boolean))]);

// Several syllables can be added at once, separated by commas (e.g. kha, qha, vhra).
const addSyllable = () => {
    draftRomanized.value.split(',').map((part) => part.trim().slice(0, 20)).filter(Boolean).forEach((romanized) => {
        if (!syllables.value.some((item) => item.romanized.trim() === romanized)) {
            syllables.value.push({ ipa: '', romanized });
        }
    });
    draftRomanized.value = '';
};

const removeSyllable = (index) => syllables.value.splice(index, 1);

const randomItem = (items) => items[Math.floor(Math.random() * items.length)];

const clamp = (value, min, max) => Math.min(Math.max(Number(value) || min, min), max);

const makeWord = (max) => {
    const length = 1 + Math.floor(Math.random() * max);
    return Array.from({ length }, () => randomItem(romanizedSyllables.value)).join('');
};

// Swadesh meaning (lowercase, with and without its gloss) -> word already in the lexicon.
const lexiconWordFor = (meaning) => {
    const full = meaning.toLowerCase();
    const loose = stripGloss(full);
    const row = words.value.find((entry) => {
        const own = entry.meaning.trim().toLowerCase();
        return entry.word.trim() !== '' && own !== '' && (own === full || stripGloss(own) === loose);
    });
    return row ? row.word.trim() : null;
};

const generateWords = () => {
    const max = clamp(generator.maxSyllables, 1, 8);
    generated.value = Array.from({ length: clamp(generator.count, 1, 500) }, () => makeWord(max));
};

const PRONOUNS = ['I', 'you (singular)', 'he', 'we', 'you (plural)', 'they'];
const DETERMINERS = ['this', 'that'];
const PREPOSITIONS = ['at', 'in', 'with'];
const ADJECTIVES = [
    'big', 'long', 'wide', 'thick', 'heavy', 'small', 'short', 'narrow', 'thin', 'red', 'green', 'yellow', 'white', 'black',
    'warm', 'cold', 'full', 'new', 'old', 'good', 'bad', 'rotten', 'dirty', 'straight', 'round', 'sharp', 'dull', 'smooth', 'wet', 'dry',
];
const NOT_NOUNS = new Set([
    ...PRONOUNS, ...DETERMINERS, ...PREPOSITIONS, ...ADJECTIVES,
    'here', 'there', 'who', 'what', 'where', 'when', 'how', 'not', 'all', 'many', 'some', 'few', 'other', 'one', 'two', 'three',
    'four', 'five', 'correct', 'near', 'far', 'right', 'left', 'and', 'if', 'because',
]);

// Each sentence follows one of these patterns, so the glosses read like real English
// (not necessarily grammatical): "I wash because dirty", "we see the fire in the forest".
const SENTENCE_PATTERNS = [
    ['pronoun', 'verb'],
    ['pronoun', 'verb', 'noun'],
    ['pronoun', 'not', 'verb', 'noun'],
    ['pronoun', 'verb', 'preposition', 'noun'],
    ['pronoun', 'verb', 'because', 'adjective'],
    ['pronoun', 'verb', 'because', 'pronoun', 'verb'],
    ['pronoun', 'verb', 'and', 'pronoun', 'verb'],
    ['pronoun', 'verb', 'noun', 'if', 'pronoun', 'verb'],
    ['determiner', 'noun', 'adjective'],
    ['determiner', 'noun', 'not', 'adjective'],
    ['who', 'verb', 'noun'],
    ['noun', 'adjective', 'because', 'noun'],
];

const wordClasses = computed(() => {
    const has = (item) => props.swadesh.includes(item);
    const list = props.swadesh;

    return {
        pronoun: PRONOUNS.filter(has),
        determiner: DETERMINERS.filter(has),
        preposition: PREPOSITIONS.filter(has),
        adjective: ADJECTIVES.filter(has),
        verb: list.filter((item) => item.startsWith('to ')),
        noun: list.filter((item) => !item.startsWith('to ') && !NOT_NOUNS.has(item)),
        not: list.filter((item) => item === 'not'),
        because: list.filter((item) => item === 'because'),
        and: list.filter((item) => item === 'and'),
        if: list.filter((item) => item === 'if'),
        who: list.filter((item) => item === 'who'),
    };
});

// Random sentences built from Swadesh meanings. A meaning already in the lexicon
// reuses that word; a new meaning gets one generated word, repeated wherever it recurs.
const generatePassage = () => {
    const max = clamp(generator.maxSyllables, 1, 8);
    const classes = wordClasses.value;
    const patterns = SENTENCE_PATTERNS.filter((pattern) => pattern.every((slot) => classes[slot]?.length));
    const chosen = new Map();

    const tokenFor = (meaning) => {
        if (!chosen.has(meaning)) {
            const existing = lexiconWordFor(meaning);
            let word = existing ?? makeWord(max);
            for (let attempt = 0; !existing && lexiconHas(word) && attempt < 8; attempt += 1) {
                word = makeWord(max);
            }
            chosen.set(meaning, word);
        }
        return { word: chosen.get(meaning), meaning };
    };

    if (!patterns.length) {
        generatedText.value = [];
        return;
    }

    const makeSentence = () => randomItem(patterns).map((slot) => tokenFor(randomItem(classes[slot])));

    // Each paragraph is 3 to 6 sentences, shown as running text with its English reading below.
    generatedText.value = Array.from({ length: clamp(generator.paragraphs, 1, 8) }, () => {
        const sentences = Array.from({ length: 3 + Math.floor(Math.random() * 4) }, makeSentence);
        const gloss = sentences
            .map((tokens) => {
                const line = tokens.map((token) => stripGloss(token.meaning)).join(' ');
                return `${line.charAt(0).toUpperCase()}${line.slice(1)}.`;
            })
            .join(' ');

        return { sentences, gloss };
    });
};

const generateText = () => {
    generated.value = [];
    generatedText.value = [];
    if (!romanizedSyllables.value.length) {
        return;
    }
    if (generator.mode === 'text') {
        generatePassage();
    } else {
        generateWords();
    }
};

const lexiconHas = (word) => words.value.some((row) => row.word.trim() === word);

const addGeneratedWord = (word, meaning = '') => {
    if (!lexiconHas(word)) {
        words.value.push({ word, meaning, notes: '' });
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
    <form method="POST" class="language-form" :action="formAction" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="formMethod !== 'POST'" type="hidden" name="_method" :value="formMethod">

        <DraftBanner :restored-at="restoredAt" @discard="discardDraft" />

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
            <p class="hint">Enter each syllable as written, e.g. <em>Le</em> or <em>Mon</em>. Separate several with commas: <em>kha, qha, vhra</em>.</p>

            <div class="field-row">
                <input id="draft-romanized" v-model="draftRomanized" autocomplete="off" aria-label="New syllables" placeholder="e.g. Le or kha, qha, vhra" @keydown.enter.prevent="addSyllable">
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
            <p class="hint">Builds sample words, or a short random text, from your syllables. Click a word to add it to the lexicon.</p>
            <div class="generator-controls">
                <label class="inline-field">Show as
                    <select v-model="generator.mode">
                        <option value="words">A set of words</option>
                        <option value="text">A piece of text</option>
                    </select>
                </label>
                <label v-if="generator.mode === 'words'" class="inline-field">Words
                    <input v-model.number="generator.count" type="number" min="1" max="500">
                </label>
                <label v-else class="inline-field">Paragraphs
                    <input v-model.number="generator.paragraphs" type="number" min="1" max="8">
                </label>
                <label class="inline-field">Max syllables per word
                    <input v-model.number="generator.maxSyllables" type="number" min="1" max="8">
                </label>
                <button type="button" class="btn small" :disabled="!romanizedSyllables.length" @click="generateText">Generate</button>
            </div>
            <p v-if="!romanizedSyllables.length" class="hint">Add some syllables first.</p>
            <p v-if="generator.mode === 'text' && generatedText.length" class="hint">Each paragraph is built from Swadesh meanings, with a rough English reading underneath. Hover a word to see its meaning. Words already in your lexicon are reused.</p>
            <div v-if="generatedText.length" class="generated-text">
                <div v-for="(paragraph, paragraphIndex) in generatedText" :key="paragraphIndex" class="generated-paragraph">
                    <p>
                        <template v-for="(sentence, sentenceIndex) in paragraph.sentences" :key="sentenceIndex">
                            <template v-for="(token, tokenIndex) in sentence" :key="tokenIndex">
                        <button
                            type="button"
                            class="text-word"
                            :class="{ added: lexiconHas(token.word) }"
                            :disabled="lexiconHas(token.word)"
                            :title="(token.meaning ? token.meaning + ' — ' : '') + (lexiconHas(token.word) ? 'already in lexicon' : 'click to add to lexicon')"
                            @click="addGeneratedWord(token.word, token.meaning)"
                        >{{ token.word }}</button>{{ tokenIndex < sentence.length - 1 ? ' ' : '. ' }}
                            </template>
                        </template>
                    </p>
                    <small class="text-gloss">{{ paragraph.gloss }}</small>
                </div>
            </div>
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
                <option v-for="item in missingSwadesh" :key="item" :value="item" />
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
            <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
            <a :href="cancelUrl">Cancel</a>
            <DraftStatus :state="draftState" :saved-at="savedAt" />
        </div>
    </form>
</template>
