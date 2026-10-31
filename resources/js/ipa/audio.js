import recordings from './recordings.json';
import { isPlayable, playSynth } from './synth.js';

/**
 * Real recordings (Wikimedia Commons, see public/audio/ipa/CREDITS.txt) are
 * played when one exists for the symbol; anything else, or any recording that
 * fails to load, falls back to the synthesiser in synth.js.
 *
 * Loading is deferred: nothing is fetched until the page has loaded and the
 * browser is idle (see preloadRecordings), or until a sound is first played.
 */
export const DEFAULT_BASE_URL = '/audio/ipa/';

const cache = new Map();
const queue = [];
let current = null;
let draining = false;

export const hasRecording = (symbol) => Object.hasOwn(recordings, symbol);

export const isPlayableIpa = (symbol) => hasRecording(symbol) || isPlayable(symbol);

const audioFor = (symbol, baseUrl) => {
    const url = `${baseUrl}${recordings[symbol]}`;
    if (!cache.has(url)) {
        const element = new Audio();
        element.preload = 'none';
        element.src = url;
        cache.set(url, element);
    }
    return cache.get(url);
};

const whenIdle = (callback) => {
    const run = () => ('requestIdleCallback' in window
        ? window.requestIdleCallback(callback, { timeout: 3000 })
        : setTimeout(callback, 200));

    if (document.readyState === 'complete') {
        run();
    } else {
        window.addEventListener('load', run, { once: true });
    }
};

const drain = () => {
    const item = queue.shift();
    if (item === undefined) {
        draining = false;
        return;
    }

    const { element } = item;
    element.preload = 'auto';
    element.load();
    whenIdle(drain);
};

/**
 * Warm the browser cache for these symbols, one file at a time, once the page
 * has loaded and the browser is idle. Safe to call repeatedly.
 */
export const preloadRecordings = (symbols, baseUrl = DEFAULT_BASE_URL) => {
    if (typeof window === 'undefined') {
        return;
    }

    symbols.filter(hasRecording).forEach((symbol) => {
        const element = audioFor(symbol, baseUrl);
        if (element.preload === 'none' && !queue.some((item) => item.element === element)) {
            queue.push({ element });
        }
    });

    if (!draining && queue.length) {
        draining = true;
        whenIdle(drain);
    }
};

/**
 * Play one IPA symbol. Returns true if something was (or is being) played.
 */
export const playIpa = (symbol, baseUrl = DEFAULT_BASE_URL) => {
    if (typeof window === 'undefined') {
        return false;
    }

    if (!hasRecording(symbol)) {
        return playSynth(symbol);
    }

    current?.pause();
    const element = audioFor(symbol, baseUrl);
    current = element;
    element.currentTime = 0;
    element.play().catch(() => {
        if (current === element) {
            playSynth(symbol);
        }
    });

    return true;
};

let sequenceId = 0;

/**
 * Play several symbols one after another (e.g. a syllable). A newer call
 * cancels any sequence still running. Each sound is cut short at stepMs so
 * the result sounds like one syllable rather than separate clips.
 */
export const playSequence = async (symbols, stepMs = 450, baseUrl = DEFAULT_BASE_URL) => {
    const id = ++sequenceId;

    for (const symbol of symbols) {
        if (id !== sequenceId) {
            return;
        }
        playIpa(symbol, baseUrl);
        await new Promise((resolve) => setTimeout(resolve, stepMs));
    }
};
