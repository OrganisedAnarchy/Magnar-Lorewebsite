<script setup>
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { createMarkdownEditor } from './admin-editor.js';
import { formatSelection, imageEmbed, renderMarkdown } from './markdown-formatting.js';
import { reportFailure, sendForm } from './http.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
    name: { type: String, required: true },
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    linkStatuses: { type: Array, default: () => [] },
    maxLength: { type: Number, default: null },
    required: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    // Default alt text for uploaded images, normally the title of what is being edited.
    altText: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);
const config = inject('markdownEditorConfig');
const editorHost = ref(null);
const imageInput = ref(null);
const uploading = ref(false);
const invalid = ref(false);
let editor;

const tools = [
    { format: 'heading', label: 'Heading', text: 'H' },
    { format: 'bold', label: 'Bold', text: 'B' },
    { format: 'italic', label: 'Italic', text: 'I' },
    { format: 'quote', label: 'Quote', text: 'Quote' },
    { format: 'bullet', label: 'Bulleted list', text: 'List' },
    { format: 'numbered', label: 'Numbered list', text: '1. List' },
    { format: 'link', label: 'Link', text: 'Link' },
    { format: 'wikilink', label: 'Lore link', text: '[[ ]]' },
    { format: 'code', label: 'Code', text: 'Code' },
];

const applyFormat = (format) => {
    const { from, to } = editor.state.selection.main;
    const change = formatSelection(editor.state.doc.toString(), from, to, format);
    editor.dispatch({
        changes: { from: change.from, to: change.to, insert: change.insert },
        selection: { anchor: change.anchor, head: change.head },
        userEvent: 'input',
    });
    editor.focus();
};

const uploadImage = async (event) => {
    const file = event.target.files[0];
    event.target.value = '';
    if (!file) {
        return;
    }

    uploading.value = true;
    const body = new FormData();
    body.append('image', file);
    const result = await sendForm(config.imageUploadUrl, body);
    uploading.value = false;

    if (!result.ok) {
        reportFailure(result);
        return;
    }

    const alt = props.altText.trim() || file.name.replace(/\.[^.]+$/, '');
    const insert = imageEmbed(result.data.url, alt);
    const { from, to } = editor.state.selection.main;
    editor.dispatch({
        changes: { from, to, insert },
        selection: { anchor: from + insert.length },
        userEvent: 'input',
    });
    editor.focus();
};

const onInvalid = () => {
    invalid.value = true;
    editor.focus();
};

watch(() => props.modelValue, (value) => {
    invalid.value = false;
    value = value ?? '';
    if (editor && editor.state.doc.toString() !== value) {
        editor.dispatch({ changes: { from: 0, to: editor.state.doc.length, insert: value }, filter: false });
    }
});

onMounted(() => {
    editor = createMarkdownEditor(editorHost.value, {
        ...config,
        doc: props.modelValue ?? '',
        onChange: (value) => emit('update:modelValue', value),
        label: props.label,
        placeholder: props.placeholder,
        maxLength: props.maxLength,
        required: props.required,
        linkStatuses: props.linkStatuses,
    });
});

onBeforeUnmount(() => editor?.destroy());
</script>

<template>
    <div class="markdown-field" :class="{ 'markdown-field-compact': compact }">
        <div class="markdown-toolbar" role="toolbar" :aria-label="`${label} formatting`">
            <button v-for="tool in tools" :key="tool.format" type="button" :aria-label="tool.label" :title="tool.label" @mousedown.prevent @click="applyFormat(tool.format)">{{ tool.text }}</button>
            <button type="button" aria-label="Upload image" title="Upload an image and insert it" :disabled="uploading" @mousedown.prevent @click="imageInput.click()">{{ uploading ? 'Uploading…' : 'Image' }}</button>
            <input ref="imageInput" type="file" accept="image/png,image/jpeg,image/webp,image/gif" hidden tabindex="-1" @change="uploadImage">
        </div>
        <div class="markdown-panes" :class="{ 'has-preview': showPreview }">
            <div ref="editorHost" class="markdown-editor" />
            <div v-if="showPreview" class="markdown-preview" :aria-label="`${label} preview`" @click.prevent v-html="preview" />
        </div>
        <textarea v-if="required" :value="modelValue" :name="name" class="markdown-validation" tabindex="-1" aria-hidden="true" required :maxlength="maxLength ?? undefined" @invalid.prevent="onInvalid" />
        <input v-else type="hidden" :name="name" :value="modelValue">
        <div v-if="invalid" class="error" role="alert">This field is required.</div>
    </div>
</template>
