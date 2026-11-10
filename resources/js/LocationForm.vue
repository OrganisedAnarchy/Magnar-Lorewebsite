<script setup>
import { computed, reactive, ref } from 'vue';
import MarkdownEditor from './MarkdownEditor.vue';
import ImageField from './ImageField.vue';
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
    currentMapUrl: { type: String, default: null },
    currentImageUrl: { type: String, default: null },
    imageLabel: { type: String, default: 'Image' },
    hasChildren: { type: Boolean, default: false },
    isChartBody: { type: Boolean, default: false },
    celestialKinds: { type: Array, default: () => [] },
    locationTypes: { type: Array, default: () => [] },
    buildingType: { type: String, default: 'Building' },
    settlementType: { type: String, default: 'Religious Settlement' },
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
    summary: props.initial.summary ?? '',
    celestial_kind: props.initial.celestial_kind ?? '',
    is_celestial_body: Boolean(props.initial.is_celestial_body),
    location_type: props.initial.location_type ?? '',
    building_type: props.initial.building_type ?? '',
    settlement_type: props.initial.settlement_type ?? '',
});

const isCelestialBody = computed(() => form.is_celestial_body);

// The chosen map file can't be kept in a draft, so only the text fields, parent and pin are restored.
const { draftState, restoredAt, savedAt, discardDraft, markSaved } = useFormDraft({
    key: props.draftKey,
    snapshot: () => ({ ...form }),
    apply: (values) => Object.assign(form, values),
    hasErrors: Object.keys(props.errors).length > 0,
});

const currentMap = ref(props.currentMapUrl);
const currentImage = ref(props.currentImageUrl);
const mapInput = ref(null);

const { formAction, formMethod, formErrors, saving, submit } = useAjaxSubmit({
    action: props.action,
    method: props.method,
    errors: props.errors,
    onSaved: (data) => {
        currentMap.value = data.map_url ?? null;
        currentImage.value = data.image_url ?? null;
        form.remove_map = false;
        markSaved(data.id);
        if (newMapPreview.value) {
            URL.revokeObjectURL(newMapPreview.value);
            newMapPreview.value = null;
        }
        if (mapInput.value) {
            mapInput.value.value = '';
        }
    },
});

const error = (name) => formErrors.value[name]?.[0];
const newMapPreview = ref(null);

const parentMapUrl = computed(() => props.parentOptions.find((option) => String(option.id) === form.parent_id)?.map_url ?? null);
const hasPin = computed(() => form.pin_x !== '' && form.pin_y !== '');
const mapShown = computed(() => newMapPreview.value ?? (form.remove_map ? null : currentMap.value));

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
    <form method="POST" enctype="multipart/form-data" :action="formAction" @submit.prevent="submit">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="formMethod !== 'POST'" type="hidden" name="_method" :value="formMethod">

        <DraftBanner :restored-at="restoredAt" @discard="discardDraft" />

        <label for="name">Name</label>
        <input id="name" name="name" v-model="form.name" maxlength="255" required>
        <div v-if="error('name')" class="error">{{ error('name') }}</div>

        <label class="checkbox">
            <input type="hidden" name="is_celestial_body" value="0">
            <input type="checkbox" name="is_celestial_body" value="1" v-model="form.is_celestial_body" :disabled="isChartBody">
            Is celestial body<span v-if="isChartBody" class="hint"> (always on for bodies on the celestial chart)</span>
        </label>
        <input v-if="isChartBody" type="hidden" name="is_celestial_body" value="1">
        <div v-if="error('is_celestial_body')" class="error">{{ error('is_celestial_body') }}</div>

        <template v-if="isCelestialBody">
            <label for="celestial_kind">Kind of celestial body</label>
            <select id="celestial_kind" name="celestial_kind" v-model="form.celestial_kind" required>
                <option value="" disabled>Choose a kind</option>
                <option v-for="kind in celestialKinds" :key="kind" :value="kind">{{ kind }}</option>
            </select>
            <div v-if="error('celestial_kind')" class="error">{{ error('celestial_kind') }}</div>

            <label>Summary</label>
            <MarkdownEditor v-model="form.summary" name="summary" label="Summary" :max-length="1000" :alt-text="form.name" required compact />
            <p class="hint">Shown when hovering the body on the celestial chart.</p>
            <div v-if="error('summary')" class="error">{{ error('summary') }}</div>
        </template>

        <template v-else>
            <label for="location_type">Type of location</label>
            <select id="location_type" name="location_type" v-model="form.location_type">
                <option value="">Not specified</option>
                <option v-for="type in locationTypes" :key="type" :value="type">{{ type }}</option>
            </select>
            <div v-if="error('location_type')" class="error">{{ error('location_type') }}</div>

            <template v-if="form.location_type === buildingType">
                <label for="building_type">Type of building</label>
                <input id="building_type" name="building_type" v-model="form.building_type" maxlength="255">
                <div v-if="error('building_type')" class="error">{{ error('building_type') }}</div>
            </template>

            <template v-if="form.location_type === settlementType">
                <label for="settlement_type">Type of settlement</label>
                <input id="settlement_type" name="settlement_type" v-model="form.settlement_type" maxlength="255">
                <div v-if="error('settlement_type')" class="error">{{ error('settlement_type') }}</div>
            </template>
        </template>

        <ImageField :label="imageLabel" :current-url="currentImage" :error="error('image')" />

        <label>Lore</label>
        <MarkdownEditor v-model="form.description" name="description" label="Lore" :alt-text="form.name" />
        <p v-if="isCelestialBody" class="hint">Separate paragraphs with a blank line.</p>
        <div v-if="error('description')" class="error">{{ error('description') }}</div>

        <template v-if="!isCelestialBody">
            <label for="parent_id">Located inside</label>
            <select id="parent_id" name="parent_id" v-model="form.parent_id" @change="onParentChange">
                <option value="">Top level (no parent)</option>
                <option v-for="option in parentOptions" :key="option.id" :value="String(option.id)">{{ option.name }}</option>
            </select>
            <p class="hint">Only locations that have a map can contain other locations. Celestial bodies with a map can be chosen too.</p>
            <div v-if="error('parent_id')" class="error">{{ error('parent_id') }}</div>
        </template>

        <template v-if="form.parent_id && !isCelestialBody">
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
        <input id="map" ref="mapInput" name="map" type="file" accept="image/png,image/jpeg,image/webp" @change="onMapChosen">
        <p class="hint">Optional. Adding a map lets this location contain child locations; without one it is a single pin that shows its lore.</p>
        <div v-if="error('map')" class="error">{{ error('map') }}</div>
                <label v-if="currentMap" class="checkbox">
            <input type="checkbox" name="remove_map" value="1" v-model="form.remove_map" :disabled="hasChildren">
            Remove the current map<span v-if="hasChildren" class="hint"> (not possible while it has child locations)</span>
        </label>
        <img v-if="mapShown" class="map-preview" :src="mapShown" alt="Map preview">

        <div class="form-actions">
            <button class="btn" type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save location' }}</button>
            <a :href="cancelUrl">Cancel</a>
            <DraftStatus :state="draftState" :saved-at="savedAt" />
        </div>
    </form>
</template>
