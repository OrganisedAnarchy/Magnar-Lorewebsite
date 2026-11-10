<script setup>
import { computed, reactive } from 'vue';
import MarkdownEditor from './MarkdownEditor.vue';
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
    errors: { type: Object, default: () => ({}) },
    categories: { type: Object, required: true },
    subCategories: { type: Object, default: () => ({}) },
    linkStatuses: { type: Array, default: () => [] },
    uncategorized: { type: Boolean, default: false },
});

const dateParts = { day: [1, 30], month: [1, 13], cycle: [1, 2], year: [0, null] };
const dateGroups = { dob: 'Date of birth (DOB)', dod: 'Date of death (DOD)' };

const fieldNames = [
    'category', 'sub_category', 'title', 'description',
    ...Object.keys(dateGroups).flatMap((prefix) => [
        ...Object.keys(dateParts).map((part) => `${prefix}_${part}`),
        `${prefix}_is_circa`, `${prefix}_is_unknown`,
    ]),
];

const pick = (source) => Object.fromEntries(fieldNames.map((name) => [
    name,
    name.includes('_is_') ? Boolean(Number(source[name] ?? 0)) : (source[name] ?? ''),
]));

const form = reactive(pick(props.initial));

const { draftState, restoredAt, savedAt, discardDraft, markSaved } = useFormDraft({
    key: props.draftKey,
    snapshot: () => pick(form),
    apply: (values) => Object.assign(form, pick(values)),
    hasErrors: Object.keys(props.errors).length > 0,
});

const isPeople = computed(() => form.category === 'people');
const subCategoryOptions = computed(() => props.subCategories[form.category] ?? []);
const categoryList = computed(() => Object.entries(props.categories));

const { formAction, formMethod, formErrors, saving, submit } = useAjaxSubmit({
    action: props.action,
    method: props.method,
    errors: props.errors,
    onSaved: (data) => {
        markSaved(data.id);
        // Family and organisation sections depend on the saved category.
        window.dispatchEvent(new CustomEvent('entry-saved', { detail: { extras: data.extras } }));
    },
});

const error = (name) => formErrors.value[name]?.[0];
</script>

<template>
    <form method="POST" class="entry-form" :action="formAction" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="formMethod !== 'POST'" type="hidden" name="_method" :value="formMethod">

        <DraftBanner :restored-at="restoredAt" @discard="discardDraft" />

        <div id="Highlevel-details" class="highlevel-details">
            <label for="category">Category</label>
            <select id="category" v-model="form.category" name="category" required>
                <option value="">Choose a category…</option>
                <option v-for="[key, info] in categoryList" :key="key" :value="key">{{ info.label }} — {{ info.examples }}</option>
            </select>
            <div v-if="error('category')" class="error">{{ error('category') }}</div>
            <div v-if="uncategorized && !form.category" class="hint">This entry is uncategorized — choose a category to save it.</div>

            <label for="sub_category">Sub category <span class="hint">(optional — pick one or type a new one)</span></label>
            <input id="sub_category" v-model="form.sub_category" name="sub_category" list="sub-category-options" maxlength="100" autocomplete="off">
            <datalist id="sub-category-options">
                <option v-for="name in subCategoryOptions" :key="name" :value="name" />
            </datalist>
            <div v-if="error('sub_category')" class="error">{{ error('sub_category') }}</div>

            <label for="title">Title</label>
            <input id="title" v-model="form.title" name="title" required>
            <div v-if="error('title')" class="error">{{ error('title') }}</div>

            <fieldset v-show="isPeople" id="people-dates">
                <legend>People &amp; beings dates</legend>
                <p class="hint">Use day-month-cycle-year. Days are 1–30, months are 1–13, and cycles are 1 or 2. Leave a date blank if it isn't recorded, tick Circa if it is approximate, or tick Unknown if it can't be known.</p>
                <div class="date-groups">
                    <fieldset v-for="(label, prefix) in dateGroups" :key="prefix" class="date-group">
                        <legend>{{ label }}</legend>
                        <div class="date-flags">
                            <label class="checkbox"><input v-model="form[`${prefix}_is_circa`]" type="checkbox" :name="`${prefix}_is_circa`" value="1" :disabled="form[`${prefix}_is_unknown`]"> Circa</label>
                            <label class="checkbox"><input v-model="form[`${prefix}_is_unknown`]" type="checkbox" :name="`${prefix}_is_unknown`" value="1"> Unknown</label>
                        </div>
                        <div class="date-fields">
                            <div v-for="(limits, part) in dateParts" :key="part" class="date-field">
                                <div class="date-control">
                                    <input
                                        :id="`${prefix}_${part}`"
                                        v-model="form[`${prefix}_${part}`]"
                                        :name="`${prefix}_${part}`"
                                        type="number"
                                        :min="limits[0]"
                                        :max="limits[1] ?? undefined"
                                        step="1"
                                        placeholder=" "
                                        :disabled="form[`${prefix}_is_unknown`]"
                                    >
                                    <label :for="`${prefix}_${part}`">{{ part.charAt(0).toUpperCase() + part.slice(1) }}</label>
                                </div>
                                <div v-if="error(`${prefix}_${part}`)" class="error">{{ error(`${prefix}_${part}`) }}</div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </fieldset>
        </div>

        <div class="main-fields">
            <label>Description
                <span class="info-tip">
                    <button type="button" aria-label="Markdown and linking help" aria-describedby="description-help">i</button>
                    <span id="description-help" class="info-tip-text" role="tooltip">Markdown. Link with [[Title]], [[Title|Alias]], [[Title@Category]] or [[Title|Alias@Category]]. Add #Heading to jump to a heading on that page, e.g. [[Title#Heading]]. Wrap a heading's text in single brackets, e.g. [Heading], to link to it within this page. Categories include Locations, Languages and Books, which link to their own pages. Type 3+ letters for suggestions (Tab completes); Ctrl/Cmd+click a link to open it, creating it if needed.</span>
                </span>
            </label>
            <MarkdownEditor v-model="form.description" name="description" label="Description" :link-statuses="linkStatuses" />
            <div v-if="error('description')" class="error">{{ error('description') }}</div>

            <div class="form-actions">
                <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save' }}</button>
                <DraftStatus :state="draftState" :saved-at="savedAt" />
            </div>
        </div>
    </form>
</template>
