<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    authenticated: {
        type: Boolean,
        default: false,
    },
    admin: {
        type: Boolean,
        default: false,
    },
    userName: {
        type: String,
        default: '',
    },
    csrfToken: {
        type: String,
        default: '',
    },
    celestialBodies: {
        type: Object,
        required: true,
    },
    admin: {
        type: Boolean,
        default: false,
    },
});

const PLANET_ORBITS = [
    { slug: 'ogunja', radius: 35, duration: 34 },
    { slug: 'amial', radius: 40.5, duration: 42 },
    { slug: 'hanar', radius: 46, duration: 50 },
    { slug: 'misana', radius: 51.5, duration: 58 },
    { slug: 'lysus', radius: 60.5, duration: 66 },
];
const randomAngle = () => Math.random() * 360;
const brightSunStart = randomAngle();
const moonStarts = [randomAngle(), randomAngle(), randomAngle()];
const mweziPhase = Math.floor(Math.random() * 8);
const ambrossIsHalf = Math.random() >= 0.5;
const planetStarts = PLANET_ORBITS.map(() => randomAngle());
const heliaPlanetIndex = Math.floor(Math.random() * (PLANET_ORBITS.length - 1));
const heliaAngle = randomAngle();
const isPaused = ref(false);
const planets = computed(() => PLANET_ORBITS.map((planet, index) => ({
    ...props.celestialBodies[planet.slug],
    ...planet,
    angle: planetStarts[index],
    x: 50 + Math.sin(planetStarts[index] * Math.PI / 180) * planet.radius,
    y: 50 - Math.cos(planetStarts[index] * Math.PI / 180) * planet.radius,
})));
const heliaPlanet = computed(() => planets.value[heliaPlanetIndex]);
const heliaPosition = computed(() => {
    const angle = heliaAngle * Math.PI / 180;

    return {
        x: heliaPlanet.value.x + Math.sin(angle) * 8,
        y: heliaPlanet.value.y - Math.cos(angle) * 8,
    };
});
const hoveredBodySlug = ref(null);
const activeBody = computed(() => props.celestialBodies[hoveredBodySlug.value] ?? null);
const heliaBrightness = computed(() => {
    const brightAngle = brightSunStart * Math.PI / 180;
    const eluminorPosition = {
        x: 50 + Math.sin(brightAngle) * 29,
        y: 50 - Math.cos(brightAngle) * 29,
    };
    const distance = Math.hypot(eluminorPosition.x - heliaPosition.value.x, eluminorPosition.y - heliaPosition.value.y);
    const proximity = Math.max(0, 1 - distance / 78);

    return (0.7 + proximity * 0.7).toFixed(2);
});

function orbitStyle(startAngle, duration) {
    return {
        '--orbit-angle': `${startAngle}deg`,
        '--orbit-duration': `${duration}s`,
    };
}

function heliaOrbitStyle() {
    return {
        '--orbit-angle': `${heliaAngle}deg`,
        '--orbit-duration': '20s',
    };
}

function bodyUrl(slug) {
    return `/celestial-bodies/${encodeURIComponent(slug)}`;
}

function showBody(slug) {
    hoveredBodySlug.value = slug;
}

function hideBody() {
    hoveredBodySlug.value = null;
}

</script>

<template>
    <main class="landing">
        <header class="site-header">
            <a class="wordmark" href="/" aria-label="The World of Magnar home">
                <span class="wordmark-mark" aria-hidden="true">M</span>
                <span>THE WORLD OF <span class="wordmark-light">MAGNAR</span></span>
            </a>

            <nav class="header-actions" aria-label="Account">
                <template v-if="authenticated">
                    <span class="member-name">Welcome, {{ userName }}</span>
                    <a v-if="admin" class="button button-quiet" href="/admin">Admin</a>
                    <form action="/logout" method="post">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button class="button button-quiet" type="submit">Sign out</button>
                    </form>
                </template>
                <template v-else>
                    <a class="button button-quiet" href="/login">Sign in</a>
                    <a class="button button-small" href="/register">Create an account</a>
                </template>
            </nav>
        </header>

        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow"><span class="eyebrow-line"></span>History is told by the victors</p>
                <h1 id="hero-title">Magnar is where the brave live</br> for all the <em>cowards are dead.</em></h1>
                <p class="hero-description">
                    Create a character, choose which gods they believe in, where they were born, and their race, then keep the details they would know close at hand.
                </p>
                <div class="hero-actions">
                    <a v-if="!authenticated" class="button button-primary" href="/register">
                        Retrieve your chronicle <span aria-hidden="true">↗</span>
                    </a>
                    <a v-if="!authenticated" class="text-link" href="/login">Already have an account? Sign in</a>
                </div>
            </div>

            <div
                class="hero-art"
                :class="{ 'is-paused': isPaused }"
                role="group"
                aria-label="Moving celestial chart of Magnar, its moons, suns, and five neighboring planets"
            >
                <div
                    class="orbit-path moon-orbit moon-orbit-one"
                    :style="orbitStyle(moonStarts[0], 10)"
                >
                    <a
                        class="orbital-link moon-link mwezi-link"
                        :href="bodyUrl(celestialBodies.mwezi.slug)"
                        :aria-label="`${celestialBodies.mwezi.name}: ${celestialBodies.mwezi.summary}`"
                        @mouseenter="showBody('mwezi')"
                        @mouseleave="hideBody"
                        @focus="showBody('mwezi')"
                        @blur="hideBody"
                    >
                        <span class="moon moon-mwezi" :class="`phase-${mweziPhase}`">
                            <span class="moon-shadow"></span>
                        </span>
                        <span class="orbit-label moon-label mwezi-label">{{ celestialBodies.mwezi.name }}</span>
                    </a>
                </div>
                <div
                    class="orbit-path moon-orbit moon-orbit-two"
                    :style="orbitStyle(moonStarts[1], 16)"
                >
                    <a
                        class="orbital-link moon-link ambross-link"
                        :href="bodyUrl(celestialBodies.ambross.slug)"
                        :aria-label="`${celestialBodies.ambross.name}: ${celestialBodies.ambross.summary}`"
                        @mouseenter="showBody('ambross')"
                        @mouseleave="hideBody"
                        @focus="showBody('ambross')"
                        @blur="hideBody"
                    >
                        <span class="moon moon-ambross" :class="{ 'is-half': ambrossIsHalf }">
                            <span class="moon-shadow"></span>
                        </span>
                        <span class="orbit-label moon-label ambross-label">{{ celestialBodies.ambross.name }}</span>
                    </a>
                </div>
                <div
                    class="orbit-path moon-orbit moon-orbit-three"
                    :style="orbitStyle(moonStarts[2], 27)"
                >
                    <span class="aegis-wanderer">
                        <a
                            class="orbital-link moon-link aegis-link"
                            :href="bodyUrl(celestialBodies.aegis.slug)"
                            :aria-label="`${celestialBodies.aegis.name}: ${celestialBodies.aegis.summary}`"
                            @mouseenter="showBody('aegis')"
                            @mouseleave="hideBody"
                            @focus="showBody('aegis')"
                            @blur="hideBody"
                        >
                            <span class="moon moon-outer"></span>
                            <span class="orbit-label moon-label aegis-label">{{ celestialBodies.aegis.name }}</span>
                        </a>
                    </span>
                </div>
                <div
                    class="orbit-path sun-orbit sun-orbit-large"
                    :style="orbitStyle(brightSunStart, 46)"
                >
                    <a
                        class="orbital-link sun-link eluminor-link"
                        :href="bodyUrl(celestialBodies.eluminor.slug)"
                        :aria-label="`${celestialBodies.eluminor.name}: ${celestialBodies.eluminor.summary}`"
                        @mouseenter="showBody('eluminor')"
                        @mouseleave="hideBody"
                        @focus="showBody('eluminor')"
                        @blur="hideBody"
                    >
                        <span class="sun sun-large"></span>
                        <span class="orbit-label sun-label">{{ celestialBodies.eluminor.name }}</span>
                    </a>
                </div>
                <button
                    class="orbit-toggle"
                    type="button"
                    :aria-pressed="isPaused"
                    @click="isPaused = !isPaused"
                >
                    {{ isPaused ? 'Resume motion' : 'Pause motion' }}
                </button>
                <div
                    v-for="planet in planets"
                    :key="planet.slug"
                    class="orbit-path planet-orbit"
                    :class="`planet-orbit-${planet.slug}`"
                    :style="orbitStyle(planet.angle, planet.duration)"
                >
                    <div class="planet-position">
                        <a
                            class="orbital-link planet-link"
                            :class="`planet-link-${planet.slug}`"
                            :href="bodyUrl(planet.slug)"
                            :aria-label="`${planet.name}: ${planet.summary}`"
                            @mouseenter="showBody(planet.slug)"
                            @mouseleave="hideBody"
                            @focus="showBody(planet.slug)"
                            @blur="hideBody"
                        >
                            <span class="planet" :class="`planet-${planet.slug}`"></span>
                            <span class="orbit-label planet-name-label">{{ planet.name }}</span>
                        </a>
                        <div
                            v-if="planet.slug === heliaPlanet.slug"
                            class="helia-local-orbit"
                            :style="heliaOrbitStyle()"
                        >
                            <a
                                class="orbital-link sun-link helia-link"
                                :href="bodyUrl(celestialBodies.helia.slug)"
                                :aria-label="`${celestialBodies.helia.name}: ${celestialBodies.helia.summary}`"
                                @mouseenter="showBody('helia')"
                                @mouseleave="hideBody"
                                @focus="showBody('helia')"
                                @blur="hideBody"
                            >
                                <span class="sun sun-small" :style="{ '--helia-brightness': heliaBrightness }"></span>
                                <span class="orbit-label sun-label">{{ celestialBodies.helia.name }}</span>
                            </a>
                        </div>
                    </div>
                </div>
                <a
                    class="orbital-link magnar-link"
                    :href="bodyUrl(celestialBodies.magnar.slug)"
                    :aria-label="`${celestialBodies.magnar.name}: ${celestialBodies.magnar.summary}`"
                    @mouseenter="showBody('magnar')"
                    @mouseleave="hideBody"
                    @focus="showBody('magnar')"
                    @blur="hideBody"
                >
                    <span class="planet magnar"></span>
                    <span class="planet-label">{{ celestialBodies.magnar.name }}</span>
                </a>
                <Transition name="body-note">
                    <p v-if="activeBody" class="body-note" role="status">
                        <strong>{{ activeBody.name }}</strong>
                        <span>{{ activeBody.summary }}</span>
                    </p>
                </Transition>
                <span class="art-star art-star-one">✳</span>
                <span class="art-star art-star-two">✦</span>
            </div>
        </section>

        <footer class="site-footer">
            <span>THE WORLD OF MAGNAR</span>
            <span>Make room for wonder.</span>
        </footer>
    </main>
</template>
