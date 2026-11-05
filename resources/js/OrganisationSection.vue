<script setup>
import { ref } from 'vue';
import { useSectionRequest } from './useSectionRequest.js';

const props = defineProps({
    state: { type: Object, required: true },
});
const emit = defineEmits(['replace']);

const dateParts = [
    { part: 'day', min: 1, max: 30 },
    { part: 'month', min: 1, max: 13 },
    { part: 'cycle', min: 1, max: 2 },
    { part: 'year', min: 0, max: null },
];

const rankKey = ref(0);
const memberKey = ref(0);
const editing = ref(null);
const dialog = ref(null);

const { busy, request, error } = useSectionRequest((extras) => {
    if (extras?.organisation) {
        emit('replace', extras.organisation);
    }
});

const addRank = async (event) => {
    if (await request(props.state.rank_store_url, { form: event.currentTarget, scope: 'rank' })) {
        rankKey.value += 1;
    }
};

const removeRank = (rank) => {
    if (window.confirm(`Remove the rank "${rank.name}"?`)) {
        request(rank.destroy_url, { method: 'DELETE' });
    }
};

const addMember = async (event) => {
    if (await request(props.state.member_store_url, { form: event.currentTarget, scope: 'member' })) {
        memberKey.value += 1;
    }
};

const removeMember = (member) => {
    if (window.confirm(`Remove ${member.name} from this organisation?`)) {
        request(member.destroy_url, { method: 'DELETE' });
    }
};

const openEditor = (member) => {
    editing.value = member;
    dialog.value?.showModal();
};

const closeEditor = () => {
    dialog.value?.close();
};

const saveMember = async (event) => {
    const member = editing.value;
    if (await request(member.update_url, { method: 'PUT', form: event.currentTarget, scope: `edit-${member.id}` })) {
        closeEditor();
    }
};
</script>

<template>
    <div>
        <section id="organisation-ranks" class="organisation-section">
            <h2>Rank hierarchy</h2>
            <p class="hint">Level 1 is the highest rank; higher numbers sit lower in the hierarchy.</p>
            <ul>
                <li v-for="rank in state.ranks" :key="rank.id">
                    <strong>{{ rank.level }}.</strong> {{ rank.name }}
                    <span class="hint">({{ rank.members_label }})</span>
                    <button class="btn" type="button" :disabled="busy" @click="removeRank(rank)">Remove</button>
                </li>
                <li v-if="!state.ranks.length" class="hint">No ranks defined yet.</li>
            </ul>
            <form :key="`rank-${rankKey}`" @submit.prevent="addRank">
                <label for="rank_name">Rank name</label>
                <input id="rank_name" name="name" maxlength="100" required>
                <div v-if="error('rank', 'name')" class="error">{{ error('rank', 'name') }}</div>
                <label for="rank_level">Level</label>
                <input id="rank_level" name="level" type="number" min="1" max="65535" :value="state.next_level" required>
                <div v-if="error('rank', 'level')" class="error">{{ error('rank', 'level') }}</div>
                <button class="btn" type="submit" :disabled="busy">Add rank</button>
            </form>
        </section>

        <section id="organisation-members" class="organisation-section">
            <h2>Known members</h2>
            <table>
                <thead>
                    <tr><th>Member</th><th>Rank</th><th>Joined (day-month-cycle-year)</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <tr v-for="member in state.members" :key="member.id">
                        <td>{{ member.name }}</td>
                        <td>{{ member.rank ?? '—' }}</td>
                        <td>{{ member.join_label ?? 'Unknown' }}</td>
                        <td>{{ member.is_current ? 'Current' : 'Former' }}</td>
                        <td>
                            <div class="row-actions">
                                <button class="btn small" type="button" @click="openEditor(member)">Edit</button>
                                <button class="btn btn-danger small" type="button" :disabled="busy" @click="removeMember(member)">Remove</button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!state.members.length"><td colspan="5" class="hint">No known members yet.</td></tr>
                </tbody>
            </table>

            <dialog ref="dialog" class="edit-dialog" @close="editing = null">
                <form v-if="editing" :key="editing.id" @submit.prevent="saveMember">
                    <h3>Edit {{ editing.name }}</h3>
                    <label for="edit_rank">Rank</label>
                    <select id="edit_rank" name="organisation_rank_id" :value="editing.organisation_rank_id ?? ''">
                        <option value="">No rank</option>
                        <option v-for="rank in state.ranks" :key="rank.id" :value="rank.id">{{ rank.name }}</option>
                    </select>
                    <div v-if="error(`edit-${editing.id}`, 'organisation_rank_id')" class="error">{{ error(`edit-${editing.id}`, 'organisation_rank_id') }}</div>
                    <fieldset class="date-group">
                        <legend>Join date <span class="hint">(optional)</span></legend>
                        <div class="date-fields">
                            <div v-for="field in dateParts" :key="field.part">
                                <label :for="`edit_${field.part}`">{{ field.part.charAt(0).toUpperCase() + field.part.slice(1) }}</label>
                                <input :id="`edit_${field.part}`" :name="`join_${field.part}`" type="number" :min="field.min" :max="field.max" :value="editing[`join_${field.part}`]">
                                <div v-if="error(`edit-${editing.id}`, `join_${field.part}`)" class="error">{{ error(`edit-${editing.id}`, `join_${field.part}`) }}</div>
                            </div>
                        </div>
                        <label class="checkbox"><input type="checkbox" name="join_is_circa" value="1" :checked="editing.join_is_circa"> Circa</label>
                    </fieldset>
                    <label class="checkbox"><input type="checkbox" name="is_current" value="1" :checked="editing.is_current"> Current member</label>
                    <div class="form-actions">
                        <button class="btn" type="submit" :disabled="busy">{{ busy ? 'Saving…' : 'Save' }}</button>
                        <button class="btn btn-secondary" type="button" @click="closeEditor">Cancel</button>
                    </div>
                </form>
            </dialog>

            <h3>Add a member</h3>
            <form :key="`member-${memberKey}`" @submit.prevent="addMember">
                <label for="member_id">Person</label>
                <select id="member_id" name="member_id" required>
                    <option value="">Choose a person…</option>
                    <option v-for="person in state.people" :key="person.id" :value="person.id">{{ person.title }}</option>
                </select>
                <div v-if="error('member', 'member_id')" class="error">{{ error('member', 'member_id') }}</div>
                <label for="new_member_rank">Rank</label>
                <select id="new_member_rank" name="organisation_rank_id">
                    <option value="">No rank</option>
                    <option v-for="rank in state.ranks" :key="rank.id" :value="rank.id">{{ rank.name }}</option>
                </select>
                <div v-if="error('member', 'organisation_rank_id')" class="error">{{ error('member', 'organisation_rank_id') }}</div>
                <fieldset>
                    <legend>Join date <span class="hint">(optional; days 1–30, months 1–13, cycles 1–2)</span></legend>
                    <template v-for="field in dateParts" :key="field.part">
                        <input :name="`join_${field.part}`" type="number" :min="field.min" :max="field.max" :placeholder="field.part.charAt(0).toUpperCase() + field.part.slice(1)" :aria-label="`Join ${field.part}`">
                        <div v-if="error('member', `join_${field.part}`)" class="error">{{ error('member', `join_${field.part}`) }}</div>
                    </template>
                    <label class="checkbox"><input type="checkbox" name="join_is_circa" value="1"> Circa</label>
                </fieldset>
                <label class="checkbox"><input type="checkbox" name="is_current" value="1" checked> Current member (untick for a former member)</label>
                <button class="btn" type="submit" :disabled="busy">Add member</button>
            </form>
        </section>
    </div>
</template>
