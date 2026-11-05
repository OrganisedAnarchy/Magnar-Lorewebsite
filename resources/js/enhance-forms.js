import { reportFailure, sendForm } from './http.js';
import { toast } from './toast.js';

/**
 * Index-page delete forms marked `data-ajax` are submitted with fetch and answered with a toast.
 *  - `data-confirm="…"` asks first.
 *  - `data-remove="tr"` removes the closest matching element on success.
 */
async function handleSubmit(event) {
    const form = event.target.closest?.('form[data-ajax]');
    if (!form) {
        return;
    }

    event.preventDefault();

    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        return;
    }

    const buttons = [...form.querySelectorAll('button[type="submit"]')];
    buttons.forEach((button) => { button.disabled = true; });

    const result = await sendForm(form.action, new FormData(form));
    buttons.forEach((button) => { button.disabled = false; });

    if (!result.ok) {
        reportFailure(result);
        return;
    }

    toast.success(result.data.message);
    form.closest(form.dataset.remove)?.remove();
}

export function enhanceForms() {
    document.addEventListener('submit', handleSubmit);
}
