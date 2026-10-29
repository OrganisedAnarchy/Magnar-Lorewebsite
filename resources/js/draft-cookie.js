// Cookies hold ~4KB each, so a draft is split across numbered chunks.
const CHUNK_SIZE = 3000;
const MAX_CHUNKS = 12;
const MAX_AGE = 60 * 60 * 24 * 7;

const readCookies = () => Object.fromEntries(
    document.cookie
        .split('; ')
        .filter(Boolean)
        .map((pair) => {
            const index = pair.indexOf('=');

            return [pair.slice(0, index), pair.slice(index + 1)];
        }),
);

const writeCookie = (name, value, maxAge) => {
    document.cookie = `${name}=${value}; path=/admin; max-age=${maxAge}; SameSite=Lax`;
};

export function clearDraft(key) {
    const cookies = readCookies();
    const count = Number(cookies[`${key}_n`] ?? 0);

    for (let i = 0; i < Math.max(count, MAX_CHUNKS); i++) {
        writeCookie(`${key}_${i}`, '', 0);
    }
    writeCookie(`${key}_n`, '', 0);
}

export function loadDraft(key) {
    const cookies = readCookies();
    const count = Number(cookies[`${key}_n`] ?? 0);
    if (!count) {
        return null;
    }

    let encoded = '';
    for (let i = 0; i < count; i++) {
        if (cookies[`${key}_${i}`] === undefined) {
            return null;
        }
        encoded += cookies[`${key}_${i}`];
    }

    try {
        return JSON.parse(decodeURIComponent(encoded));
    } catch {
        return null;
    }
}

/** Returns false when the draft is too large to fit in cookies. */
export function saveDraft(key, data) {
    const encoded = encodeURIComponent(JSON.stringify(data));
    const chunks = [];
    for (let i = 0; i < encoded.length; i += CHUNK_SIZE) {
        let end = i + CHUNK_SIZE;
        // Never split a %XX escape sequence across chunks.
        if (encoded[end - 1] === '%') {
            end -= 1;
        } else if (encoded[end - 2] === '%') {
            end -= 2;
        }
        chunks.push(encoded.slice(i, end));
        i = end - CHUNK_SIZE;
    }

    if (chunks.length > MAX_CHUNKS) {
        return false;
    }

    clearDraft(key);
    chunks.forEach((chunk, i) => writeCookie(`${key}_${i}`, chunk, MAX_AGE));
    writeCookie(`${key}_n`, String(chunks.length), MAX_AGE);

    return true;
}
