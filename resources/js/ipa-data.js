export const ipaCategories = [
    {
        key: 'consonants',
        label: 'Pulmonic consonants',
        groups: [
            { label: 'Plosives', symbols: ['p', 'b', 't', 'd', 'ʈ', 'ɖ', 'c', 'ɟ', 'k', 'ɡ', 'q', 'ɢ', 'ʔ'] },
            { label: 'Nasals', symbols: ['m', 'ɱ', 'n', 'ɳ', 'ɲ', 'ŋ', 'ɴ'] },
            { label: 'Trills', symbols: ['ʙ', 'r', 'ʀ'] },
            { label: 'Taps & flaps', symbols: ['ⱱ', 'ɾ', 'ɽ'] },
            { label: 'Fricatives', symbols: ['ɸ', 'β', 'f', 'v', 'θ', 'ð', 's', 'z', 'ʃ', 'ʒ', 'ʂ', 'ʐ', 'ç', 'ʝ', 'x', 'ɣ', 'χ', 'ʁ', 'ħ', 'ʕ', 'h', 'ɦ'] },
            { label: 'Lateral fricatives', symbols: ['ɬ', 'ɮ'] },
            { label: 'Approximants', symbols: ['ʋ', 'ɹ', 'ɻ', 'j', 'ɰ', 'w'] },
            { label: 'Lateral approximants', symbols: ['l', 'ɭ', 'ʎ', 'ʟ'] },
        ],
    },
    {
        key: 'vowels',
        label: 'Vowels',
        groups: [
            { label: 'Close', symbols: ['i', 'y', 'ɨ', 'ʉ', 'ɯ', 'u'] },
            { label: 'Near-close', symbols: ['ɪ', 'ʏ', 'ʊ'] },
            { label: 'Close-mid', symbols: ['e', 'ø', 'ɘ', 'ɵ', 'ɤ', 'o'] },
            { label: 'Mid', symbols: ['ə'] },
            { label: 'Open-mid', symbols: ['ɛ', 'œ', 'ɜ', 'ɞ', 'ʌ', 'ɔ'] },
            { label: 'Near-open', symbols: ['æ', 'ɐ'] },
            { label: 'Open', symbols: ['a', 'ɶ', 'ɑ', 'ɒ'] },
        ],
    },
    {
        key: 'affricates',
        label: 'Affricates',
        groups: [
            { label: 'Affricates', symbols: ['t͡s', 'd͡z', 't͡ʃ', 'd͡ʒ', 't͡ɕ', 'd͡ʑ', 'ʈ͡ʂ', 'ɖ͡ʐ', 'p͡f', 'k͡x', 'q͡χ'] },
        ],
    },
    {
        key: 'nonpulmonic',
        label: 'Non-pulmonic',
        groups: [
            { label: 'Clicks', symbols: ['ʘ', 'ǀ', 'ǃ', 'ǂ', 'ǁ'] },
            { label: 'Implosives', symbols: ['ɓ', 'ɗ', 'ʄ', 'ɠ', 'ʛ'] },
            { label: 'Ejectives', symbols: ['pʼ', 'tʼ', 'kʼ', 'qʼ', 'sʼ', 'tsʼ', 'tʃʼ'] },
        ],
    },
    {
        key: 'other',
        label: 'Other & suprasegmentals',
        groups: [
            { label: 'Other symbols', symbols: ['ʍ', 'ɥ', 'ɕ', 'ʑ', 'ɧ'] },
            { label: 'Length & stress', symbols: ['ː', 'ˑ', 'ˈ', 'ˌ'] },
            { label: 'Tone', symbols: ['˥', '˦', '˧', '˨', '˩'] },
            { label: 'Diacritics', symbols: ['ʰ', 'ʷ', 'ʲ', 'ˠ', '̃', '̥', '̬', '̩'] },
        ],
    },
];

export const knownSymbols = new Set(
    ipaCategories.flatMap((category) => category.groups.flatMap((group) => group.symbols)),
);
