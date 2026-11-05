// Drafts live in localStorage: unlike cookies they are not sent with every request, so large lore texts are safe.
export function clearDraft(key) {
    try {
        localStorage.removeItem(key);
    } catch {
        // Storage unavailable (e.g. private mode); nothing to clear.
    }
}

export function loadDraft(key) {
    try {
        const raw = localStorage.getItem(key);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

/** Returns false when the draft cannot be stored (quota exceeded or storage unavailable). */
export function saveDraft(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));

        return true;
    } catch {
        return false;
    }
}
