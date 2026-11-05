import { toast } from './toast.js';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/**
 * Post a form's data to Laravel and expect JSON back.
 * Never throws: network failures come back as `{ ok: false, status: 0 }` with a toast already shown.
 */
export async function sendForm(url, body, method = 'POST') {
    try {
        const response = await fetch(url, {
            method,
            body,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
        });
        const data = await response.json().catch(() => ({}));

        return { ok: response.ok, status: response.status, data };
    } catch {
        toast.error('Could not reach the server. Check your connection and try again.');

        return { ok: false, status: 0, data: {} };
    }
}

/** Show the right toast for a failed response. Returns the validation errors, if any. */
export function reportFailure({ status, data }) {
    if (status === 0) {
        return {};
    }

    if (status === 419) {
        toast.error('Your session has expired. Reload the page and try again.');
        return {};
    }

    if (status === 422 && data.errors) {
        const first = Object.values(data.errors)[0]?.[0];
        toast.error(first ?? 'Please fix the highlighted fields.');
        return data.errors;
    }

    toast.error(data.message ?? 'Something went wrong. Please try again.');

    return {};
}
