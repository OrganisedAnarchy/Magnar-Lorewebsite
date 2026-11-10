<script setup>
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import MarkdownEditor from './MarkdownEditor.vue';
import ImageField from './ImageField.vue';
import DraftBanner from './DraftBanner.vue';
import DraftStatus from './DraftStatus.vue';
import { useAjaxSubmit } from './useAjaxSubmit.js';
import { useFormDraft } from './useFormDraft.js';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    csrf: { type: String, required: true },
    draftKey: { type: String, required: true },
    initial: { type: Object, required: true },
    languageOptions: { type: Array, default: () => [] },
    currentImageUrl: { type: String, default: null },
    imageLabel: { type: String, default: 'Image' },
    errors: { type: Object, default: () => ({}) },
    searchUrl: { type: String, required: true },
    cancelUrl: { type: String, required: true },
});

const dateParts = { day: [1, 30], month: [1, 13], cycle: [1, 2], year: [0, null] };

const form = reactive({
    title: props.initial.title ?? '',
    author_name: props.initial.author_name ?? '',
    summary: props.initial.summary ?? '',
    content: props.initial.content ?? '',
    published_is_circa: Boolean(props.initial.published_is_circa),
    published_is_unknown: Boolean(props.initial.published_is_unknown),
    ...Object.fromEntries(Object.keys(dateParts).map((part) => [`published_${part}`, props.initial[`published_${part}`] ?? ''])),
});

const currentImage = ref(props.currentImageUrl);

const { formAction, formMethod, formErrors, saving, submit } = useAjaxSubmit({
    action: props.action,
    method: props.method,
    errors: props.errors,
    onSaved: (data) => {
        currentImage.value = data.image_url ?? null;
        markSaved(data.id);
    },
});

const error = (name) => formErrors.value[name]?.[0];

const authorMatches = ref([]);
const authorOpen = ref(false);
let authorTimer = null;
let authorRequest = 0;

const searchAuthors = () => {
    clearTimeout(authorTimer);
    const term = form.author_name.trim();
    if (term.length < 2) {
        authorMatches.value = [];
        return;
    }
    authorTimer = setTimeout(async () => {
        const request = ++authorRequest;
        try {
            const response = await fetch(`${props.searchUrl}?${new URLSearchParams({ q: term, category: 'people' })}`, {
                headers: { Accept: 'application/json' },
            });
            if (response.ok && request === authorRequest) {
                authorMatches.value = await response.json();
                authorOpen.value = true;
            }
        } catch {
            authorMatches.value = [];
        }
    }, 200);
};

const chooseAuthor = (title) => {
    form.author_name = title;
    authorOpen.value = false;
};

const authorExists = computed(() => authorMatches.value.some((match) => match.title.toLowerCase() === form.author_name.trim().toLowerCase()));
const newAuthor = computed(() => form.author_name.trim() !== '' && !authorExists.value);

onBeforeUnmount(() => clearTimeout(authorTimer));

const languages = ref([...(props.initial.languages ?? [])]);
const languageInput = ref('');
const languageOpen = ref(false);

const { draftState, restoredAt, savedAt, discardDraft, markSaved } = useFormDraft({
    key: props.draftKey,
    snapshot: () => ({ ...form, languages: languages.value }),
    apply: ({ languages: savedLanguages, ...values }) => {
        Object.assign(form, values);
        languages.value = [...(savedLanguages ?? [])];
    },
    hasErrors: Object.keys(props.errors).length > 0,
});

const hasLanguage = (name) => languages.value.some((existing) => existing.toLowerCase() === name.toLowerCase());

const languageSuggestions = computed(() => {
    const term = languageInput.value.trim().toLowerCase();
    return props.languageOptions.filter((name) => !hasLanguage(name) && name.toLowerCase().includes(term));
});

const addLanguage = (value) => {
    const name = value.trim();
    if (name && !hasLanguage(name)) {
        languages.value.push(name);
    }
    languageInput.value = '';
};

const removeLanguage = (name) => {
    languages.value = languages.value.filter((existing) => existing !== name);
};

const onLanguageKey = (event) => {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addLanguage(languageInput.value);
    } else if (event.key === 'Backspace' && languageInput.value === '' && languages.value.length) {
        languages.value.pop();
    }
};

const onLanguageInput = () => {
    if (languageInput.value.includes(',')) {
        const parts = languageInput.value.split(',');
        parts.slice(0, -1).forEach(addLanguage);
        languageInput.value = parts[parts.length - 1].trimStart();
    }
};

const closeLanguageList = () => {
    addLanguage(languageInput.value);
    languageOpen.value = false;
};
</script>

<template>
    <form method="POST" enctype="multipart/form-data" class="book-form" :action="formAction" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="formMethod !== 'POST'" type="hidden" name="_method" :value="formMethod">

        <DraftBanner :restored-at="restoredAt" @discard="discardDraft" />

        <section class="panel">
            <label for="title">Title</label>
            <input id="title" v-model="form.title" name="title" required>
            <div v-if="error('title')" class="error">{{ error('title') }}</div>

            <ImageField :label="imageLabel" :current-url="currentImage" :error="error('image')" />

            <label for="author_name">Author</label>
            <div class="combo">
                <input
                    id="author_name"
                    v-model="form.author_name"
                    name="author_name"
                    autocomplete="off"
                    placeholder="Search or type a name"
                    @input="searchAuthors"
                    @focus="authorOpen = true"
                    @blur="authorOpen = false"
                >
                <ul v-if="authorOpen && authorMatches.length" class="combo-list" role="listbox">
                    <li v-for="match in authorMatches" :key="match.title" role="option" @mousedown.prevent="chooseAuthor(match.title)">
                        {{ match.title }}
                    </li>
                </ul>
            </div>
            <p v-if="newAuthor && form.author_name.trim().length >= 2" class="hint">
                No matching person yet — saving will create “{{ form.author_name.trim() }}” with the note “author of [[{{ form.title || 'this book' }}]]”.
            </p>
            <div v-if="error('author_name')" class="error">{{ error('author_name') }}</div>

            <label for="language-input">Available in languages <span class="hint">(type to search, comma to add)</span></label>
            <div class="combo">
                <div class="tag-input" @click="$refs.languageField.focus()">
                    <span v-for="name in languages" :key="name" class="tag">
                        {{ name }}
                        <button type="button" :aria-label="`Remove ${name}`" @click.stop="removeLanguage(name)">✕</button>
                    </span>
                    <input
                        id="language-input"
                        ref="languageField"
                        v-model="languageInput"
                        autocomplete="off"
                        placeholder="Add language"
                        @keydown="onLanguageKey"
                        @input="onLanguageInput"
                        @focus="languageOpen = true"
                        @blur="closeLanguageList"
                    >
                </div>
                <ul v-if="languageOpen && languageSuggestions.length" class="combo-list" role="listbox">
                    <li v-for="name in languageSuggestions" :key="name" role="option" @mousedown.prevent="addLanguage(name)">{{ name }}</li>
                </ul>
            </div>
            <p class="hint">“Common” means the book is in English. Remove it if the book isn’t available in Common.</p>
            <input v-for="name in languages" :key="name" type="hidden" name="languages[]" :value="name">
            <div v-for="(messages, key) in formErrors" :key="key">
                <div v-if="key.startsWith('languages')" class="error">{{ messages[0] }}</div>
            </div>
        </section>

        <fieldset class="date-group">
            <legend>Publish date</legend>
            <div class="date-flags">
                <label class="checkbox"><input v-model="form.published_is_circa" type="checkbox" name="published_is_circa" value="1" :disabled="form.published_is_unknown"> Circa</label>
                <label class="checkbox"><input v-model="form.published_is_unknown" type="checkbox" name="published_is_unknown" value="1"> Unknown</label>
            </div>
            <div class="date-fields">
                <div v-for="(limits, part) in dateParts" :key="part" class="date-field">
                    <div class="date-control">
                        <input
                            :id="`published_${part}`"
                            v-model="form[`published_${part}`]"
                            :name="`published_${part}`"
                            type="number"
                            :min="limits[0]"
                            :max="limits[1] ?? undefined"
                            step="1"
                            placeholder=" "
                            :disabled="form.published_is_unknown"
                        >
                        <label :for="`published_${part}`">{{ part.charAt(0).toUpperCase() + part.slice(1) }}</label>
                    </div>
                    <div v-if="error(`published_${part}`)" class="error">{{ error(`published_${part}`) }}</div>
                </div>
            </div>
        </fieldset>

        <section class="panel">
            <label>Summary of contents</label>
            <MarkdownEditor v-model="form.summary" name="summary" label="Summary of contents" :alt-text="form.title" compact />
            <div v-if="error('summary')" class="error">{{ error('summary') }}</div>

            <label>Full text <span class="hint">(optional)</span></label>
            <MarkdownEditor v-model="form.content" name="content" label="Full text" :alt-text="form.title" />
            <div v-if="error('content')" class="error">{{ error('content') }}</div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
            <a :href="cancelUrl">Cancel</a>
            <DraftStatus :state="draftState" :saved-at="savedAt" />
        </div>
    </form>
</template>
