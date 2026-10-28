import { createApp } from 'vue';
import App from './App.vue';

const mountElement = document.getElementById('app');

createApp(App, {
    authenticated: mountElement.dataset.authenticated === 'true',
    admin: mountElement.dataset.admin === 'true',
    userName: mountElement.dataset.userName ?? '',
    csrfToken: document.querySelector('meta[name="csrf-token"]').content,
    celestialBodies: JSON.parse(mountElement.dataset.celestialBodies),
}).mount(mountElement);
