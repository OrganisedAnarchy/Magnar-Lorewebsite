<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    action: { type: String, required: true },
    method: { type: String, default: 'POST' },
    csrf: { type: String, required: true },
    initial: { type: Object, required: true },
    currentMapUrl: { type: String, default: null },
    hasChildren: { type: Boolean, default: false },
    parentOptions: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    cancelUrl: { type: String, required: true },
});

const form = reactive({
    name: props.initial.name ?? '',
    description: props.initial.description ?? '',
    parent_id: props.initial.parent_id ? String(props.initial.parent_id) : '',
    pin_x: props.initial.pin_x ?? '',
    pin_y: props.initial.pin_y ?? '',
    remove_map: Boolean(props.initial.remove_map),
});

const error = (name) => props.errors[name]?.[0];
const newMapPreview = ref(null);

const parentMapUrl = computed(() => props.parentOptions.find((option) => String(option.id) === form.parent_id)?.map_url ?? null);
const hasPin = computed(() => form.pin_x !== '' && form.pin_y !== '');
const mapShown = computed(() => newMapPreview.value ?? (form.remove_map ? null : props.currentMapUrl));

const onMapChosen = (event) => {
    const file = event.target.files[0];
    if (newMapPreview.value) {
        URL.revokeObjectURL(newMapPreview.value);
    }
    newMapPreview.value = file ? URL.createObjectURL(file) : null;
};

const placePin = (event) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const clamp = (value) => Math.min(100, Math.max(0, value));
    form.pin_x = clamp(((event.clientX - rect.left) / rect.width) * 100).toFixed(2);
    form.pin_y = clamp(((event.clientY - rect.top) / rect.height) * 100).toFixed(2);
};

const onParentChange = () => {
    form.pin_x = '';
    form.pin_y = '';
};
</script>

<template>
    <form method="POST" enctype="multipart/form-data" :action="action">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="method !== 'POST'" type="hidden" name="_method" :value="method">

        <label for="name">Name</label>
        <input id="name" name="name" v-model="form.name" maxlength="255" required>
        <div v-if="error('name')" class="error">{{ error('name') }}</div>

        <label for="description">Lore</label>
        <textarea id="description" name="description" v-model="form.description" rows="8"></textarea>
        <div v-if="error('description')" class="error">{{ error('description') }}</div>

        <label for="parent_id">Located inside</label>
        <select id="parent_id" name="parent_id" v-model="form.parent_id" @change="onParentChange">
            <option value="">Top level (no parent)</option>
            <option v-for="option in parentOptions" :key="option.id" :value="String(option.id)">{{ option.name }}</option>
        </select>
        <p class="hint">Only locations that have a map can contain other locations.</p>
        <div v-if="error('parent_id')" class="error">{{ error('parent_id') }}</div>

        <template v-if="form.parent_id">
            <label>Pin position on the parent's map</label>
            <p class="hint">Click the map to place this location's pin.</p>
            <div class="pin-picker" @click="placePin">
                <img :src="parentMapUrl" alt="Parent map" draggable="false">
                <span v-if="hasPin" class="pin" :style="{ left: `${form.pin_x}%`, top: `${form.pin_y}%` }"></span>
            </div>
            <input type="hidden" name="pin_x" :value="form.pin_x">
            <input type="hidden" name="pin_y" :value="form.pin_y">
            <div v-if="error('pin_x') || error('pin_y')" class="error">{{ error('pin_x') ?? error('pin_y') }}</div>
        </template>

        <label for="map">Map image</label>
        <input id="map" name="map" type="file" accept="image/png,image/jpeg,image/webp" @change="onMapChosen">
        <p class="hint">Optional. Adding a map lets this location contain child locations; without one it is a single pin that shows its lore.</p>
        <div v-if="error('map')" class="error">{{ error('map') }}</div>
        <label v-if="currentMapUrl" class="checkbox">
            <input type="checkbox" name="remove_map" value="1" v-model="form.remove_map" :disabled="hasChildren">
            Remove the current map<span v-if="hasChildren" class="hint"> (not possible while it has child locations)</span>
        </label>
        <img v-if="mapShown" class="map-preview" :src="mapShown" alt="Map preview">

        <div class="form-actions">
            <button class="btn" type="submit">Save location</button>
            <a :href="cancelUrl">Cancel</a>
        </div>
    </form>
</template>

<style scoped>
.pin-picker { position: relative; display: inline-block; max-width: 100%; cursor: crosshair; border: 1px solid #3a3450; }
.pin-picker img, .map-preview { display: block; max-width: 100%; }
.map-preview { margin-top: .75rem; max-height: 20rem; }
.pin { position: absolute; width: 1rem; height: 1rem; margin: -.5rem 0 0 -.5rem; background: #ff5a6e; border: 2px solid #fff; border-radius: 50%; pointer-events: none; }
</style>
