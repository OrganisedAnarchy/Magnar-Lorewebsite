import { reactive } from 'vue';

export const toasts = reactive([]);

let nextId = 1;

export function dismissToast(id) {
    const index = toasts.findIndex((item) => item.id === id);
    if (index !== -1) {
        toasts.splice(index, 1);
    }
}

export function notify(message, type = 'success', duration = 4000) {
    if (!message) {
        return;
    }

    const id = nextId++;
    toasts.push({ id, message, type });
    setTimeout(() => dismissToast(id), duration);
}

export const toast = {
    success: (message) => notify(message, 'success'),
    error: (message) => notify(message, 'error', 7000),
    info: (message) => notify(message, 'info'),
};
