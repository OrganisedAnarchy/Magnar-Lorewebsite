import { createApp } from 'vue';
import '@fortawesome/fontawesome-free/css/fontawesome.css';
import '@fortawesome/fontawesome-free/css/solid.css';
import EntryForm from './EntryForm.vue';
import LanguageForm from './LanguageForm.vue';
import BookForm from './BookForm.vue';
import LocationForm from './LocationForm.vue';
import ToastHost from './ToastHost.vue';
import EntryExtras from './EntryExtras.vue';
import { enhanceForms } from './enhance-forms.js';
import { toast } from './toast.js';

const toastMount = document.getElementById('toast-host');

if (toastMount) {
    createApp(ToastHost).mount(toastMount);

    // Messages flashed by a normal (non-AJAX) request, e.g. when JavaScript was unavailable for a submit.
    const flash = JSON.parse(toastMount.dataset.flash ?? 'null');
    if (flash) {
        toast.success(flash);
    }
}

enhanceForms();

const entryMount = document.getElementById('entry-form-app');
const languageMount = document.getElementById('language-form-app');
const bookMount = document.getElementById('book-form-app');
const locationMount = document.getElementById('location-form-app');
const extrasMount = document.getElementById('entry-extras-app');

if (extrasMount) {
    createApp(EntryExtras, JSON.parse(extrasMount.dataset.props)).mount(extrasMount);
}

if (locationMount) {
    createApp(LocationForm, JSON.parse(locationMount.dataset.props)).mount(locationMount);
}

if (entryMount) {
    createApp(EntryForm, JSON.parse(entryMount.dataset.props)).mount(entryMount);
}

if (languageMount) {
    createApp(LanguageForm, JSON.parse(languageMount.dataset.props)).mount(languageMount);
}

if (bookMount) {
    createApp(BookForm, JSON.parse(bookMount.dataset.props)).mount(bookMount);
}
