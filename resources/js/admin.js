import { createApp } from 'vue';
import EntryForm from './EntryForm.vue';
import LanguageForm from './LanguageForm.vue';
import BookForm from './BookForm.vue';

const entryMount = document.getElementById('entry-form-app');
const languageMount = document.getElementById('language-form-app');
const bookMount = document.getElementById('book-form-app');

if (entryMount) {
    createApp(EntryForm, JSON.parse(entryMount.dataset.props)).mount(entryMount);
}

if (languageMount) {
    createApp(LanguageForm, JSON.parse(languageMount.dataset.props)).mount(languageMount);
}

if (bookMount) {
    createApp(BookForm, JSON.parse(bookMount.dataset.props)).mount(bookMount);
}
