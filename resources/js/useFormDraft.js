import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { clearDraft, loadDraft, saveDraft } from './draft-storage.js';

/**
 * Autosaves a form's state as a local draft and restores it on the next visit.
 *
 * `snapshot` returns the plain, serialisable state to keep; `apply` writes a snapshot back into the form.
 * The form is only posted to Laravel when the user presses Save, at which point the draft is cleared.
 */
export function useFormDraft({ key, snapshot, apply, hasErrors = false, delay = 600 }) {
    const baseline = JSON.stringify(snapshot());
    const draftState = ref('idle');
    const restoredAt = ref(null);
    const savedAt = ref(null);
    let timer = null;

    // After a failed validation the submitted values are the starting point, so they are always worth keeping.
    const isUnchanged = () => !hasErrors && JSON.stringify(snapshot()) === baseline;

    if (!hasErrors) {
        const draft = loadDraft(key);
        if (draft?.values && JSON.stringify(draft.values) !== baseline) {
            apply(draft.values);
            restoredAt.value = draft.savedAt ?? null;
        }
    }

    const persist = () => {
        if (isUnchanged()) {
            clearDraft(key);
            draftState.value = 'idle';
            return;
        }

        const now = new Date().toISOString();
        if (saveDraft(key, { savedAt: now, values: snapshot() })) {
            savedAt.value = now;
            draftState.value = 'saved';
        } else {
            draftState.value = 'too-large';
        }
    };

    watch(snapshot, () => {
        draftState.value = 'pending';
        clearTimeout(timer);
        timer = setTimeout(persist, delay);
    }, { deep: true });

    const flush = () => {
        if (draftState.value === 'pending') {
            clearTimeout(timer);
            persist();
        }
    };

    const discardDraft = () => {
        clearTimeout(timer);
        clearDraft(key);
        apply(JSON.parse(baseline));
        restoredAt.value = null;
        savedAt.value = null;
        // Applying the baseline triggers the watcher; settle back to idle once it has run.
        setTimeout(() => {
            clearTimeout(timer);
            clearDraft(key);
            draftState.value = 'idle';
        }, 0);
    };

    const clearOnSubmit = () => {
        clearTimeout(timer);
        clearDraft(key);
    };

    onMounted(() => {
        window.addEventListener('beforeunload', flush);
        if (hasErrors) {
            persist();
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', flush);
        clearTimeout(timer);
    });

    return { draftState, restoredAt, savedAt, discardDraft, clearOnSubmit };
}
