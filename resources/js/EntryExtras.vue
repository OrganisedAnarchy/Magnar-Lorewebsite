<script setup>
import { ref } from 'vue';
import FamilySection from './FamilySection.vue';
import OrganisationSection from './OrganisationSection.vue';

const props = defineProps({
    initial: { type: Object, required: true },
});

const extras = ref(props.initial);

// The entry form announces each save so the sections follow the saved category.
window.addEventListener('entry-saved', (event) => {
    if (event.detail?.extras) {
        extras.value = event.detail.extras;
    }
});
</script>

<template>
    <div>
        <FamilySection v-if="extras.family" :state="extras.family" @replace="extras.family = $event" />
        <OrganisationSection v-if="extras.organisation" :state="extras.organisation" @replace="extras.organisation = $event" />
    </div>
</template>
