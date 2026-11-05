import { createApp } from 'vue';
import MapViewer from './MapViewer.vue';

const mount = document.getElementById('map-app');

createApp(MapViewer, {
    indexUrl: mount.dataset.indexUrl,
    showUrl: mount.dataset.showUrl,
    homeUrl: mount.dataset.homeUrl,
}).mount(mount);
