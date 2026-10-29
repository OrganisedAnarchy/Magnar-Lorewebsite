import { createApp } from 'vue';
import EntryForm from './EntryForm.vue';

const mountElement = document.getElementById('entry-form-app');

if (mountElement) {
    createApp(EntryForm, JSON.parse(mountElement.dataset.props)).mount(mountElement);
}
