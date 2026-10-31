/**
 * Speaks IPA with eSpeak-NG (WebAssembly, runs fully in the browser).
 * eSpeak's phoneme mode doesn't take IPA, so symbols are converted to eSpeak
 * mnemonics; sounds the en-us voice can't make are mapped to their closest one.
 * The ~18 MB engine is only downloaded the first time speech is requested.
 */
// Vendored from the espeak-ng npm package (GPL-3.0, see public/vendor/espeak-ng/LICENSE).
const WASM_URL = '/vendor/espeak-ng/espeak-ng.wasm';

const MNEMONICS = {
    p: 'p', b: 'b', t: 't', d: 'd', ʈ: 't', ɖ: 'd', c: 'c', ɟ: 'J', k: 'k', ɡ: 'g', g: 'g', q: 'q', ɢ: 'g', ʔ: '?',
    m: 'm', ɱ: 'm', n: 'n', ɳ: 'n', ɲ: 'n', ŋ: 'N', ɴ: 'N',
    ʙ: 'b', r: '*', ʀ: 'R', ⱱ: '*', ɾ: '*', ɽ: '*',
    ɸ: 'f', β: 'v', f: 'f', v: 'v', θ: 'T', ð: 'D', s: 's', z: 'z', ʃ: 'S', ʒ: 'Z', ʂ: 'S', ʐ: 'Z',
    ç: 'C', ʝ: 'C', x: 'x', ɣ: 'Q', χ: 'x', ʁ: 'R', ħ: 'h', ʕ: '?', h: 'h', ɦ: 'h', ʍ: 'w', ɕ: 'S', ʑ: 'Z', ɧ: 'S',
    ɬ: 'l#', ɮ: 'l', ʋ: 'v', ɹ: 'R', ɻ: 'R', j: 'j', ɰ: 'w', w: 'w', ɥ: 'j',
    l: 'l', ɭ: 'l', ʎ: 'l', ʟ: 'l',
    i: 'i', y: 'i', ɨ: 'I', ʉ: 'u', ɯ: 'u', u: 'u', ɪ: 'I', ʏ: 'I', ʊ: 'U',
    e: 'e', ø: 'e', ɘ: '@', ɵ: 'O', ɤ: 'o', o: 'o', ə: '@', ɛ: 'E', œ: 'E', ɜ: '3', ɞ: '3', ʌ: 'V', ɔ: 'O',
    æ: 'a', ɐ: 'a', a: 'a', ɶ: 'a', ɑ: 'A:', ɒ: '0',
    ɓ: 'b', ɗ: 'd', ʄ: 'J', ɠ: 'g', ʛ: 'G',
    ʘ: 'p', ǀ: 't', ǃ: 'k', ǂ: 'c', ǁ: 'l#',
};

const AFFRICATES = { ts: 'ts', dz: 'dz', tʃ: 'tS', dʒ: 'dZ', tɕ: 'tS', dʑ: 'dZ', ʈʂ: 'tS', ɖʐ: 'dZ', pf: 'pf', kx: 'kx', qχ: 'qx' };
const TIE = /[\u0361\u035C]/gu;
const IGNORED = /[\u0300-\u036F\u02B0-\u02B8\u02E0-\u02E4\u02E5-\u02E9\u02BC\u02C8\u02CC\u02D1]/gu;

export const ipaToEspeak = (ipa) => {
    const chars = [...ipa.replace(TIE, '').replace(IGNORED, '')];
    let result = '';

    for (let index = 0; index < chars.length; index += 1) {
        const pair = chars[index] + (chars[index + 1] ?? '');
        if (ipa.includes('\u0361') && AFFRICATES[pair]) {
            result += AFFRICATES[pair];
            index += 1;
        } else if (chars[index] === 'ː') {
            result += result.endsWith(':') ? '' : ':';
        } else if (MNEMONICS[chars[index]]) {
            result += MNEMONICS[chars[index]];
        }
    }

    return result;
};

let enginePromise = null;
let context = null;
let source = null;

const loadEngine = () => {
    enginePromise ??= import('./vendor/espeak-ng.js').then((module) => module.default);
    return enginePromise;
};

const synthesize = async (phonemes) => {
    const ESpeakNg = await loadEngine();
    const engine = await ESpeakNg({
        arguments: ['-w', 'speech.wav', '-v', 'en-us', '-s', '130', '-p', '45', `[[${phonemes}]]`],
        locateFile: (file) => (file.endsWith('.wasm') ? WASM_URL : file),
    });

    return engine.FS.readFile('speech.wav').buffer;
};

/**
 * Speak a string of IPA. Resolves to false when nothing speakable was found
 * or the engine failed to load, so callers can fall back to something else.
 */
export const speakIpa = async (ipa) => {
    const phonemes = ipaToEspeak(ipa);
    if (!phonemes || typeof window === 'undefined') {
        return false;
    }

    try {
        const wav = await synthesize(phonemes);
        context ??= new (window.AudioContext || window.webkitAudioContext)();
        await context.resume();
        const buffer = await context.decodeAudioData(wav.slice(0));

        source?.stop();
        source = context.createBufferSource();
        source.buffer = buffer;
        source.connect(context.destination);
        source.start();

        return true;
    } catch {
        return false;
    }
};

/**
 * Start downloading the engine ahead of the first click (idle time only).
 */
export const warmSpeech = () => {
    const start = () => loadEngine().catch(() => {});
    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(start, { timeout: 5000 });
    } else {
        setTimeout(start, 2000);
    }
};
