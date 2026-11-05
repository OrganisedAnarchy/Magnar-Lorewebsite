<script setup>
import { ref } from 'vue';
import { useSectionRequest } from './useSectionRequest.js';

const props = defineProps({
    state: { type: Object, required: true },
});
const emit = defineEmits(['replace']);

const addKey = ref(0);

const { busy, request, error } = useSectionRequest((extras) => {
    if (extras?.family) {
        emit('replace', extras.family);
    }
});

const update = (member, event) => request(member.update_url, { method: 'PUT', form: event.currentTarget, scope: `update-${member.id}` });

const remove = (member) => {
    if (window.confirm(`Remove ${member.name} from the family?`)) {
        request(member.destroy_url, { method: 'DELETE' });
    }
};

const add = async (event) => {
    if (await request(props.state.store_url, { form: event.currentTarget, scope: 'add' })) {
        addKey.value += 1;
    }
};
</script>

<template>
    <section id="family-members" class="organisation-section">
        <h2>Family</h2>
        <ul>
            <li v-for="member in state.members" :key="member.id">
                <strong>{{ member.label }}:</strong> {{ member.name }}
                <form style="display:inline" @submit.prevent="update(member, $event)">
                    <select name="relationship" :value="member.relationship" :aria-label="`Change relationship to ${member.name}`">
                        <option v-for="option in state.relationships" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <button class="btn" type="submit" :disabled="busy">Update</button>
                    <div v-if="error(`update-${member.id}`, 'relationship')" class="error">{{ error(`update-${member.id}`, 'relationship') }}</div>
                </form>
                <button class="btn" type="button" :disabled="busy" @click="remove(member)">Remove</button>
            </li>
            <li v-if="!state.members.length" class="hint">No family members yet.</li>
        </ul>

        <h3>Add a family member</h3>
        <p class="hint">Direct relations only e.g. Parents, Spouses, Children.</p>
        <form :key="addKey" @submit.prevent="add">
            <label for="family_name">Name</label>
            <input id="family_name" name="name" list="family-people" maxlength="255" required>
            <datalist id="family-people">
                <option v-for="person in state.people" :key="person" :value="person"></option>
            </datalist>
            <p class="hint">Pick an existing person, or type a new name to create them automatically.</p>
            <div v-if="error('add', 'name')" class="error">{{ error('add', 'name') }}</div>
            <label for="family_relationship">They are this person's…</label>
            <select id="family_relationship" name="relationship" required>
                <option v-for="option in state.relationships" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <div v-if="error('add', 'relationship')" class="error">{{ error('add', 'relationship') }}</div>
            <button class="btn" type="submit" :disabled="busy">{{ busy ? 'Saving…' : 'Add family member' }}</button>
        </form>
    </section>
</template>
