import { ref } from 'vue';
import { reportFailure, sendForm } from './http.js';
import { toast } from './toast.js';

/**
 * Sends the small add/update/remove requests of an entry's family and organisation sections.
 * Laravel answers every one of them with the section's fresh state, handed to `applyExtras`.
 *
 * Validation errors are tagged with a `scope` so only the form that was submitted shows them.
 */
export function useSectionRequest(applyExtras) {
    const busy = ref(false);
    const failure = ref({ scope: null, fields: {} });

    /** Resolves true when Laravel accepted the request. */
    const request = async (url, { method = 'POST', form = null, scope = null } = {}) => {
        if (busy.value) {
            return false;
        }

        busy.value = true;
        failure.value = { scope: null, fields: {} };

        const body = form ? new FormData(form) : new FormData();
        if (method !== 'POST') {
            body.append('_method', method);
        }

        const result = await sendForm(url, body);
        busy.value = false;

        if (!result.ok) {
            failure.value = { scope, fields: reportFailure(result) };
            return false;
        }

        toast.success(result.data.message);
        applyExtras(result.data.extras);

        return true;
    };

    const error = (scope, field) => (failure.value.scope === scope ? failure.value.fields[field]?.[0] : undefined);

    return { busy, request, error };
}
