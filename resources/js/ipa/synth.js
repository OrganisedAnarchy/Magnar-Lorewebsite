/**
 * Approximate IPA sounds synthesised in the browser with the Web Audio API
 * (source-filter model: voiced buzz or noise shaped by formant filters).
 * Used as a fallback for symbols that have no recording.
 */

const MODIFIERS = /[\u0300-\u036F\u02B0-\u02B8\u02E0-\u02E4\u02E5-\u02E9\u02BC\u02D0\u02D1\u02C8\u02CC]/gu;

const vowels = {
    i: [280, 2250, 2900], y: [280, 1750, 2150], ɨ: [300, 1900, 2500], ʉ: [300, 1350, 2200], ɯ: [300, 1350, 2300], u: [310, 870, 2250],
    ɪ: [400, 1900, 2550], ʏ: [400, 1600, 2300], ʊ: [450, 1000, 2300],
    e: [400, 2100, 2700], ø: [400, 1600, 2300], ɘ: [400, 1600, 2400], ɵ: [400, 1200, 2300], ɤ: [400, 1100, 2300], o: [420, 800, 2300],
    ə: [500, 1500, 2500],
    ɛ: [550, 1850, 2600], œ: [550, 1500, 2400], ɜ: [550, 1400, 2400], ɞ: [550, 1200, 2400], ʌ: [650, 1100, 2400], ɔ: [600, 900, 2300],
    æ: [700, 1700, 2500], ɐ: [700, 1400, 2400],
    a: [800, 1400, 2500], ɶ: [800, 1500, 2400], ɑ: [750, 1000, 2400], ɒ: [700, 850, 2400],
};

const glides = {
    j: vowels.i, w: vowels.u, ɰ: vowels.ɯ, ɥ: vowels.y,
    ʋ: [400, 1100, 2300], ɹ: [400, 1300, 1700], ɻ: [400, 1200, 1500],
    l: [400, 1100, 2500], ɭ: [400, 1000, 2200], ʎ: [350, 2000, 2800], ʟ: [400, 1300, 2400],
};

// [centre frequency, Q, voiced]
const fricatives = {
    ɸ: [1500, 0.5, false], β: [1500, 0.5, true], f: [4500, 0.5, false], v: [4500, 0.5, true],
    θ: [6500, 0.5, false], ð: [6500, 0.5, true], s: [8000, 2, false], z: [8000, 2, true],
    ʃ: [3200, 2, false], ʒ: [3200, 2, true], ʂ: [2600, 2, false], ʐ: [2600, 2, true],
    ɕ: [4200, 2, false], ʑ: [4200, 2, true], ç: [5000, 1, false], ʝ: [5000, 1, true],
    x: [2200, 1.5, false], ɣ: [2200, 1.5, true], χ: [1300, 1, false], ʁ: [1300, 1, true],
    ħ: [1000, 1, false], ʕ: [1000, 1, true], h: [1500, 0.7, false], ɦ: [1500, 0.7, true],
    ɬ: [4500, 1.5, false], ɮ: [4500, 1.5, true], ɧ: [2500, 1, false], ʍ: [900, 1, false],
};

// [burst frequency, voiced]
const stops = {
    p: [600, false], b: [600, true], t: [4000, false], d: [4000, true],
    ʈ: [3000, false], ɖ: [3000, true], c: [3500, false], ɟ: [3500, true],
    k: [2000, false], ɡ: [2000, true], q: [1000, false], ɢ: [1000, true], ʔ: [300, false],
    ɓ: [600, true], ɗ: [4000, true], ʄ: [3500, true], ɠ: [2000, true], ʛ: [1000, true],
};

const nasals = { m: 1100, ɱ: 1300, n: 1500, ɳ: 1700, ɲ: 2000, ŋ: 2200, ɴ: 1100 };

// [formants, modulation rate in Hz, duration in seconds]
const trills = {
    ʙ: [[400, 900, 2200], 22, 0.5], r: [[400, 1300, 1700], 25, 0.5], ʀ: [[450, 1100, 2300], 30, 0.5],
    ⱱ: [[400, 1000, 2200], 0, 0.12], ɾ: [[400, 1300, 1700], 0, 0.12], ɽ: [[400, 1200, 1500], 0, 0.12],
};

// [burst frequency, bandwidth Q]
const clicks = { ʘ: [800, 1], ǀ: [4500, 2], ǃ: [1500, 1], ǂ: [3200, 2], ǁ: [2600, 1.5] };

const lookup = (char) => {
    if (vowels[char]) return { kind: 'vowel', formants: vowels[char] };
    if (glides[char]) return { kind: 'glide', formants: glides[char] };
    if (fricatives[char]) return { kind: 'fricative', fricative: fricatives[char] };
    if (stops[char]) return { kind: 'stop', stop: stops[char] };
    if (nasals[char]) return { kind: 'nasal', f2: nasals[char] };
    if (trills[char]) return { kind: 'trill', trill: trills[char] };
    if (clicks[char]) return { kind: 'click', click: clicks[char] };
    return null;
};

const parse = (symbol) => {
    const chars = [...symbol.replace(MODIFIERS, '')];
    const segments = chars.map(lookup).filter(Boolean);

    return {
        segments,
        long: symbol.includes('ː'),
        aspirated: symbol.includes('ʰ'),
        ejective: symbol.includes('ʼ'),
        nasalized: symbol.includes('\u0303'),
    };
};

export const isPlayable = (symbol) => parse(symbol).segments.length > 0;

let context = null;
let noiseBuffer = null;

const getContext = () => {
    context ??= new (window.AudioContext || window.webkitAudioContext)();
    if (context.state === 'suspended') {
        context.resume();
    }
    return context;
};

const getNoise = (c) => {
    if (!noiseBuffer) {
        noiseBuffer = c.createBuffer(1, c.sampleRate, c.sampleRate);
        const data = noiseBuffer.getChannelData(0);
        for (let i = 0; i < data.length; i += 1) {
            data[i] = Math.random() * 2 - 1;
        }
    }
    return noiseBuffer;
};

const envelope = (c, out, t, dur, peak, attack = 0.02, release = 0.05) => {
    const gain = c.createGain();
    const hold = Math.max(t + attack, t + dur - release);
    gain.gain.setValueAtTime(0, t);
    gain.gain.linearRampToValueAtTime(peak, t + attack);
    gain.gain.setValueAtTime(peak, hold);
    gain.gain.linearRampToValueAtTime(0, t + dur);
    gain.connect(out);
    return gain;
};

const buzz = (c, t, dur) => {
    const osc = c.createOscillator();
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(130, t);
    osc.frequency.linearRampToValueAtTime(105, t + dur);
    osc.start(t);
    osc.stop(t + dur + 0.05);
    return osc;
};

const noise = (c, t, dur) => {
    const source = c.createBufferSource();
    source.buffer = getNoise(c);
    source.loop = true;
    source.start(t);
    source.stop(t + dur + 0.05);
    return source;
};

const bandpass = (c, source, destination, frequency, q, gain = 1) => {
    const filter = c.createBiquadFilter();
    filter.type = 'bandpass';
    filter.frequency.value = frequency;
    filter.Q.value = q;
    const level = c.createGain();
    level.gain.value = gain;
    source.connect(filter);
    filter.connect(level);
    level.connect(destination);
};

const shapeFormants = (c, source, destination, formants) => {
    const levels = [3, 2.2, 1.2];
    const widths = [6, 9, 14];
    formants.forEach((frequency, index) => bandpass(c, source, destination, frequency, widths[index], levels[index]));
};

const voicedSound = (c, out, t, dur, formants, peak = 0.5, modulation = 0) => {
    const level = envelope(c, out, t, dur, peak);
    let target = level;

    if (modulation > 0) {
        const flutter = c.createGain();
        flutter.gain.value = 0.5;
        const lfo = c.createOscillator();
        const depth = c.createGain();
        depth.gain.value = 0.5;
        lfo.frequency.value = modulation;
        lfo.connect(depth);
        depth.connect(flutter.gain);
        lfo.start(t);
        lfo.stop(t + dur + 0.05);
        flutter.connect(level);
        target = flutter;
    }

    shapeFormants(c, buzz(c, t, dur), target, formants);
    return t + dur;
};

const noiseSound = (c, out, t, dur, frequency, q, peak) => {
    const level = envelope(c, out, t, dur, peak, 0.03, 0.06);
    bandpass(c, noise(c, t, dur), level, frequency, q, 2);
};

const burst = (c, out, t, frequency, q, peak) => {
    const level = c.createGain();
    level.gain.setValueAtTime(peak, t);
    level.gain.linearRampToValueAtTime(0, t + 0.03);
    level.connect(out);
    bandpass(c, noise(c, t, 0.03), level, frequency, q, 2.5);
};

const voiceBar = (c, out, t, dur) => {
    const level = envelope(c, out, t, dur, 0.25, 0.01, 0.01);
    const filter = c.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.value = 350;
    buzz(c, t, dur).connect(filter);
    filter.connect(level);
};

const schedule = (c, out, symbol) => {
    const { segments, long, aspirated, ejective, nasalized } = parse(symbol);
    const solo = segments.length === 1;
    const unit = (solo ? 0.5 : 0.3) * (long ? 1.8 : 1);
    let t = c.currentTime + 0.02;

    segments.forEach((segment, index) => {
        const next = segments[index + 1];

        if (segment.kind === 'vowel' || segment.kind === 'glide') {
            const formants = segment.formants;
            t = voicedSound(c, out, t, segment.kind === 'glide' ? unit * 0.8 : unit, formants, 0.5);
            if (nasalized && segment.kind === 'vowel') {
                voicedSound(c, out, t - unit, unit, [250, 1000, 2400], 0.15);
            }
        } else if (segment.kind === 'fricative') {
            const [frequency, q, voiced] = segment.fricative;
            const dur = unit * (index > 0 && segments[index - 1].kind === 'stop' ? 0.5 : 1);
            noiseSound(c, out, t, dur, frequency, q, 0.45);
            if (voiced) {
                voicedSound(c, out, t, dur, [200, 700, 2200], 0.18);
            }
            t += dur;
        } else if (segment.kind === 'nasal') {
            t = voicedSound(c, out, t, unit, [250, segment.f2, 2500], 0.4);
        } else if (segment.kind === 'trill') {
            const [formants, rate, dur] = segment.trill;
            t = voicedSound(c, out, t, dur * (long ? 1.8 : 1), formants, 0.5, rate);
        } else if (segment.kind === 'click') {
            const [frequency, q] = segment.click;
            burst(c, out, t, frequency, q, 1);
            t += 0.25;
        } else if (segment.kind === 'stop') {
            const [frequency, voiced] = segment.stop;
            const closure = 0.07;
            if (voiced) {
                voiceBar(c, out, t, closure);
            }
            burst(c, out, t + closure, frequency, 1.2, ejective ? 1.6 : 1);
            t += closure + 0.03;

            if (aspirated) {
                noiseSound(c, out, t, 0.07, 1500, 0.7, 0.3);
                t += 0.07;
            }
            if (solo && frequency !== 300) {
                t = voicedSound(c, out, t, 0.28, vowels.a, 0.4);
            } else if (next && next.kind === 'fricative') {
                t += 0.01;
            }
        }
    });
};

/**
 * Play one IPA symbol (or short sequence). Returns false if nothing could be played.
 */
export const playSynth = (symbol) => {
    if (typeof window === 'undefined' || !(window.AudioContext || window.webkitAudioContext) || !isPlayable(symbol)) {
        return false;
    }

    const c = getContext();
    const out = c.createGain();
    out.gain.value = 0.7;
    out.connect(c.destination);
    schedule(c, out, symbol);

    return true;
};
