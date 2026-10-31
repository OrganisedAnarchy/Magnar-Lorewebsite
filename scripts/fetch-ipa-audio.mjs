/**
 * Downloads IPA recordings from Wikimedia Commons into public/audio/ipa and
 * writes resources/js/ipa/recordings.json (symbol -> file) plus a credits file.
 *
 *   node scripts/fetch-ipa-audio.mjs
 */
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';

const API = 'https://commons.wikimedia.org/w/api.php';
const OUT = 'public/audio/ipa';
const HEADERS = { 'User-Agent': 'MagnarLoreWebsite/1.0 (https://github.com/OrganisedAnarchy/Magnar-Lorewebsite)' };

const files = {
    p: 'Voiceless bilabial plosive', b: 'Voiced bilabial plosive', t: 'Voiceless alveolar plosive', d: 'Voiced alveolar plosive',
    ʈ: 'Voiceless retroflex plosive', ɖ: 'Voiced retroflex plosive', c: 'Voiceless palatal plosive', ɟ: 'Voiced palatal plosive',
    k: 'Voiceless velar plosive', ɡ: 'Voiced velar plosive', q: 'Voiceless uvular plosive', ɢ: 'Voiced uvular plosive', ʔ: 'Glottal stop',
    m: 'Bilabial nasal', ɱ: 'Labiodental nasal', n: 'Alveolar nasal', ɳ: 'Retroflex nasal', ɲ: 'Palatal nasal', ŋ: 'Velar nasal', ɴ: 'Uvular nasal',
    ʙ: 'Bilabial trill', r: 'Alveolar trill', ʀ: 'Uvular trill', ⱱ: 'Labiodental flap', ɾ: 'Alveolar tap', ɽ: 'Retroflex flap',
    ɸ: 'Voiceless bilabial fricative', β: 'Voiced bilabial fricative', f: 'Voiceless labiodental fricative', v: 'Voiced labiodental fricative',
    θ: 'Voiceless dental fricative', ð: 'Voiced dental fricative', s: 'Voiceless alveolar sibilant', z: 'Voiced alveolar sibilant',
    ʃ: 'Voiceless palato-alveolar sibilant', ʒ: 'Voiced palato-alveolar sibilant', ʂ: 'Voiceless retroflex sibilant', ʐ: 'Voiced retroflex sibilant',
    ç: 'Voiceless palatal fricative', ʝ: 'Voiced palatal fricative', x: 'Voiceless velar fricative', ɣ: 'Voiced velar fricative',
    χ: 'Voiceless uvular fricative', ʁ: 'Voiced uvular fricative', ħ: 'Voiceless pharyngeal fricative', ʕ: 'Voiced pharyngeal fricative',
    h: 'Voiceless glottal fricative', ɦ: 'Voiced glottal fricative', ɬ: 'Voiceless alveolar lateral fricative', ɮ: 'Voiced alveolar lateral fricative',
    ʋ: 'Labiodental approximant', ɹ: 'Alveolar approximant', ɻ: 'Retroflex approximant', j: 'Palatal approximant', ɰ: 'Voiced velar approximant', w: 'Voiced labio-velar approximant',
    l: 'Alveolar lateral approximant', ɭ: 'Retroflex lateral approximant', ʎ: 'Palatal lateral approximant', ʟ: 'Velar lateral approximant',
    i: 'Close front unrounded vowel', y: 'Close front rounded vowel', ɨ: 'Close central unrounded vowel', ʉ: 'Close central rounded vowel',
    ɯ: 'Close back unrounded vowel', u: 'Close back rounded vowel', ɪ: 'Near-close near-front unrounded vowel', ʏ: 'Near-close near-front rounded vowel',
    ʊ: 'Near-close near-back rounded vowel', e: 'Close-mid front unrounded vowel', ø: 'Close-mid front rounded vowel',
    ɘ: 'Close-mid central unrounded vowel', ɵ: 'Close-mid central rounded vowel', ɤ: 'Close-mid back unrounded vowel', o: 'Close-mid back rounded vowel',
    ə: 'Mid-central vowel', ɛ: 'Open-mid front unrounded vowel', œ: 'Open-mid front rounded vowel', ɜ: 'Open-mid central unrounded vowel',
    ɞ: 'Open-mid central rounded vowel', ʌ: 'Open-mid back unrounded vowel', ɔ: 'Open-mid back rounded vowel',
    æ: 'Near-open front unrounded vowel', ɐ: 'Near-open central vowel', a: 'Open front unrounded vowel', ɶ: 'Open front rounded vowel',
    ɑ: 'Open back unrounded vowel', ɒ: 'Open back rounded vowel',
    ʍ: 'Voiceless labio-velar fricative', ɕ: 'Voiceless alveolo-palatal sibilant',
    ʑ: 'Voiced alveolo-palatal sibilant', ɧ: 'Voiceless dorso-palatal velar fricative',
    ʘ: 'Bilabial click', ǀ: 'Dental click', ǃ: 'Postalveolar click', ǁ: 'Alveolar lateral click',
    ɓ: 'Voiced bilabial implosive', ɗ: 'Voiced alveolar implosive', ʄ: 'Voiced palatal implosive', ɠ: 'Voiced velar implosive', ʛ: 'Voiced uvular implosive',
};

const stripTags = (html) => html.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();

const lookup = async (titles) => {
    const params = new URLSearchParams({
        action: 'query', format: 'json', prop: 'imageinfo', iiprop: 'url|extmetadata|user', redirects: '1',
        titles: titles.map((title) => `File:${title}.ogg`).join('|'),
    });
    const data = await (await fetch(`${API}?${params}`, { headers: HEADERS })).json();
    return {
        pages: Object.values(data.query?.pages ?? {}),
        redirects: Object.fromEntries((data.query?.redirects ?? []).map((r) => [r.from, r.to])),
    };
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const download = async (url) => {
    for (let attempt = 0; attempt < 8; attempt += 1) {
        const response = await fetch(url, { headers: HEADERS });
        if (response.status !== 429 && response.status !== 503) {
            return response;
        }
        const wait = Math.min(Number(response.headers.get('retry-after')) || 60, 900);
        console.log(`rate limited, waiting ${wait}s`);
        await sleep((wait + 5) * 1000);
    }
    return fetch(url, { headers: HEADERS });
};

mkdirSync(OUT, { recursive: true });

const entries = Object.entries(files);
const recordings = {};
const credits = [];
const missing = [];

for (let i = 0; i < entries.length; i += 20) {
    const batch = entries.slice(i, i + 20);
    const { pages, redirects } = await lookup(batch.map(([, title]) => title));
    const byTitle = Object.fromEntries(pages.map((page) => [page.title, page]));

    for (const [symbol, title] of batch) {
        const requested = `File:${title}.ogg`;
        const page = byTitle[redirects[requested] ?? requested];
        const info = page?.imageinfo?.[0];
        if (!info) {
            missing.push(`${symbol} (${title})`);
            continue;
        }

        const name = `u${[...symbol].map((c) => c.codePointAt(0).toString(16).padStart(4, '0')).join('-')}.ogg`;
        const target = `${OUT}/${name}`;
        if (existsSync(target)) {
            // already downloaded on an earlier run
        } else {
            await sleep(500);
            const audio = await download(info.url);
            if (!audio.ok) {
                missing.push(`${symbol} (download ${audio.status})`);
                continue;
            }
            writeFileSync(target, Buffer.from(await audio.arrayBuffer()));
        }

        const meta = info.extmetadata ?? {};
        recordings[symbol] = name;
        credits.push([
            symbol,
            page.title,
            stripTags(meta.Artist?.value ?? '') || `Commons uploader ${info.user} (see file page)`,
            stripTags(meta.LicenseShortName?.value ?? 'unknown'),
            meta.LicenseUrl?.value ?? '',
            `https://commons.wikimedia.org/wiki/${encodeURI(page.title.replaceAll(' ', '_'))}`,
        ].join('\t'));
    }
}

writeFileSync('resources/js/ipa/recordings.json', `${JSON.stringify(recordings, null, 2)}\n`);
writeFileSync(`${OUT}/CREDITS.txt`, [
    'IPA recordings from Wikimedia Commons (https://commons.wikimedia.org).',
    'Each file keeps its original author and licence; see the file page for the full record.',
    'Columns: symbol, Commons file, author, licence, licence URL, file page (full attribution)',
    '',
    ...credits,
    '',
].join('\n'));

console.log(`downloaded ${Object.keys(recordings).length}/${entries.length}`);
console.log('missing:', missing.join(' | ') || 'none');
console.log('licences:', [...new Set(credits.map((line) => line.split('\t')[3]))].join(' | '));
