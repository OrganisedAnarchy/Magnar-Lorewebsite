import { ref } from 'vue';
import { reportFailure, sendForm } from './http.js';
import { toast } from './toast.js';

/**
 * Submits a Vue form to Laravel with fetch, so saving never reloads the page.
 *
 * After a create, Laravel returns the update URL; the form then carries on as an edit form.
 * `onSaved(data)` runs after every successful save.
 */
export function useAjaxSubmit({ action, method, errors = {}, onSaved = null }) {
    const formAction = ref(action);
    const formMethod = ref(method);
    const formErrors = ref(errors);
    const saving = ref(false);

    const submit = async (event) => {
        if (saving.value) {
            return;
        }

        saving.value = true;
        formErrors.value = {};

        const result = await sendForm(formAction.value, new FormData(event.currentTarget));
        saving.value = false;

        if (!result.ok) {
            formErrors.value = reportFailure(result);
            return;
        }

        const { data } = result;
        toast.success(data.message);

        if (data.action) {
            formAction.value = data.action;
            formMethod.value = data.method ?? 'PUT';
        }
        if (data.edit_url) {
            history.replaceState(null, '', data.edit_url);
        }

        await onSaved?.(data);
    };

    return { formAction, formMethod, formErrors, saving, submit };
}
