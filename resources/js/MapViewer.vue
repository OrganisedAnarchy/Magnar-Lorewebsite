<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    indexUrl: { type: String, required: true },
    showUrl: { type: String, required: true },
    homeUrl: { type: String, required: true },
});

const roots = ref([]);
const current = ref(null);
const selected = ref(null);
const loading = ref(true);
const failed = ref(false);

const getJson = async (url) => {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!response.ok) {
        throw new Error(response.statusText);
    }
    return response.json();
};

const openLocation = async (id) => {
    loading.value = true;
    failed.value = false;
    selected.value = null;
    try {
        current.value = await getJson(props.showUrl.replace('__ID__', id));
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
};

const openWorld = () => {
    current.value = null;
    selected.value = null;
};

const selectRoot = (location) => {
    if (location.has_map) {
        openLocation(location.id);
    } else {
        current.value = null;
        selected.value = location;
    }
};

onMounted(async () => {
    try {
        roots.value = (await getJson(props.indexUrl)).locations;
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <main class="map-page">
        <header>
            <a :href="homeUrl">← The World of Magnar</a>
            <nav v-if="current" class="crumbs" aria-label="Breadcrumb">
                <button type="button" @click="openWorld">World</button>
                <template v-for="crumb in current.breadcrumbs" :key="crumb.id">
                    / <button type="button" @click="openLocation(crumb.id)">{{ crumb.name }}</button>
                </template>
                / <strong>{{ current.name }}</strong>
            </nav>
        </header>

        <p v-if="failed" class="notice">The map could not be loaded.</p>
        <p v-else-if="loading" class="notice">Loading…</p>

        <section v-else-if="!current">
            <h1>The World</h1>
            <p v-if="!roots.length" class="notice">No locations have been charted yet.</p>
            <ul class="root-list">
                <li v-for="location in roots" :key="location.id">
                    <button type="button" @click="selectRoot(location)">{{ location.name }}</button>
                    <span v-if="location.has_map" class="tag">Map</span>
                </li>
            </ul>
        </section>

        <section v-else>
            <h1>{{ current.name }}</h1>
            <div v-if="current.map_url" class="map-frame">
                <img :src="current.map_url" :alt="`Map of ${current.name}`" draggable="false">
                <button
                    v-for="child in current.children"
                    :key="child.id"
                    type="button"
                    class="pin"
                    :class="{ active: selected?.id === child.id }"
                    :style="{ left: `${child.pin_x}%`, top: `${child.pin_y}%` }"
                    :title="child.name"
                    :aria-label="child.name"
                    @click="selected = child"
                ></button>
            </div>
            <p v-else class="notice">This location has no map.</p>
            <p v-if="!current.children.length" class="notice">Nothing has been pinned on this map yet.</p>
        </section>

        <aside v-if="selected" class="lore-box" role="dialog" aria-label="Location lore">
                    <button type="button" class="close" aria-label="Close" @click="selected = null">×</button>
                    <h2>{{ selected.name }}</h2>
                    <p class="lore">{{ selected.description || 'Nothing is known about this place yet.' }}</p>
                    <button v-if="selected.has_map" type="button" class="enter" @click="openLocation(selected.id)">Open map</button>
                </aside>

        <aside v-if="current && current.description && !selected" class="lore-box lore-inline">
            <h2>About {{ current.name }}</h2>
            <p class="lore">{{ current.description }}</p>
        </aside>
    </main>
</template>
