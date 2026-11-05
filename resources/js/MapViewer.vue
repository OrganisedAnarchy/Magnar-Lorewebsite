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

<style>
body { margin: 0; background: #14121a; color: #e8e4f0; font-family: system-ui, sans-serif; }
.map-page { max-width: 70rem; margin: 0 auto; padding: 1rem; }
.map-page header { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; }
.map-page a, .map-page button { color: #cdb8ff; }
.crumbs button, .root-list button { background: none; border: 0; font: inherit; cursor: pointer; text-decoration: underline; }
.notice { color: #9a93ad; }
.root-list { list-style: none; padding: 0; }
.root-list li { padding: .5rem 0; }
.tag { margin-left: .5rem; padding: 0 .5rem; background: #1f1b2b; border-radius: 999px; font-size: .75rem; }
.map-frame { position: relative; display: inline-block; max-width: 100%; border: 1px solid #3a3450; }
.map-frame img { display: block; max-width: 100%; }
.pin { position: absolute; width: 1.1rem; height: 1.1rem; margin: -.55rem 0 0 -.55rem; padding: 0; background: #ff5a6e; border: 2px solid #fff; border-radius: 50%; cursor: pointer; }
.pin:hover, .pin.active { background: #6b4fd8; transform: scale(1.25); }
.lore-box { position: fixed; right: 1rem; bottom: 1rem; width: min(24rem, calc(100vw - 2rem)); max-height: 60vh; overflow: auto; padding: 1rem 1.25rem; background: #1f1b2b; border: 1px solid #3a3450; border-radius: 8px; box-shadow: 0 8px 30px rgba(0, 0, 0, .5); }
.lore-inline { position: static; width: auto; max-height: none; margin-top: 1.5rem; box-shadow: none; }
.lore-box h2 { margin-top: 0; }
.lore { white-space: pre-wrap; line-height: 1.5; }
.lore-box .close { float: right; background: none; border: 0; font-size: 1.5rem; cursor: pointer; }
.lore-box .enter { padding: .5rem 1rem; background: #6b4fd8; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
</style>
