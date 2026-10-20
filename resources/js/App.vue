<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    authenticated: {
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
});

const BRIGHT_SUN_PERIOD = 34;
const DIM_SUN_PERIOD = 57;
const MOON_PERIODS = [15, 21, 29];
const HELIA_PERIOD = 12;
const PLANET_ORBITS = [
    { slug: 'ogunja', period: 38, radius: 38, heliaOrbitRadius: 6.5 },
    { slug: 'amial', period: 39, radius: 44, heliaOrbitRadius: 6.5 },
    { slug: 'hanar', period: 40, radius: 50, heliaOrbitRadius: 6.5 },
    { slug: 'misana', period: 41, radius: 56, heliaOrbitRadius: 6.5 },
    { slug: 'lysus', period: 64, radius: 62 },
];
const HELIA_STATIONS = [
    { slug: 'magnar', orbitRadius: 32 },
    ...PLANET_ORBITS.slice(0, 4).map(({ slug, heliaOrbitRadius }) => ({
        slug,
        orbitRadius: heliaOrbitRadius,
    })),
];
const SEASONS = [
    { name: 'Summer', cycle: 1 },
    { name: 'Spring', cycle: 1 },
    { name: 'Autumn', cycle: 1 },
    { name: 'Winter', cycle: 1 },
    { name: 'Winter', cycle: 2 },
    { name: 'Autumn', cycle: 2 },
    { name: 'Spring', cycle: 2 },
    { name: 'Summer', cycle: 2 },
];
const SYNODIC_CYCLE_SECONDS = 1 / (1 / BRIGHT_SUN_PERIOD - 1 / DIM_SUN_PERIOD);
const randomAngle = () => Math.random() * 360;
const wrapAngle = (angle) => (angle % 360 + 360) % 360;
const initialYearPosition = Math.random() * 2;
const brightSunStart = randomAngle();
const moonStarts = MOON_PERIODS.map(() => randomAngle());
const planetaryOrigin = randomAngle();
const planetStartOffsets = [0, 72, 144, 216, 288].map((offset) => offset + (Math.random() - 0.5) * 24);
const planetStarts = PLANET_ORBITS.map((_, index) => wrapAngle(planetaryOrigin + planetStartOffsets[index]));

const yearPosition = ref(initialYearPosition);
const elapsedSeconds = ref(0);
const simulationPaused = ref(false);
const seasonIndex = computed(() => Math.floor(yearPosition.value * 4));
const season = computed(() => SEASONS[seasonIndex.value]);
const isAutumn = computed(() => season.value.name === 'Autumn');
const isSecondCycle = computed(() => yearPosition.value >= 1);
const mweziPhase = computed(() => Math.floor((yearPosition.value % 1) * 8));
const ambrossIsHalf = computed(() => yearPosition.value % 1 >= 0.5);
const activeHeliaIndex = computed(() => Math.min(
    Math.floor((yearPosition.value % 1) * HELIA_STATIONS.length),
    HELIA_STATIONS.length - 1,
));
const planetAngles = computed(() => PLANET_ORBITS.map((planet, index) => (
    (planetStarts[index] + elapsedSeconds.value * 360 / planet.period) % 360
)));
const planets = computed(() => PLANET_ORBITS.map((planet, index) => ({
    ...props.celestialBodies[planet.slug],
    ...planet,
    start: planetStarts[index],
    angle: planetAngles.value[index],
})));
const planetPositions = computed(() => Object.fromEntries(
    PLANET_ORBITS.map((planet, index) => {
        const angle = planetAngles.value[index] * Math.PI / 180;

        return [planet.slug, {
            x: 50 + Math.sin(angle) * planet.radius,
            y: 50 - Math.cos(angle) * planet.radius,
        }];
    }),
));
const heliaAgents = computed(() => {
    const stations = isSecondCycle.value ? [...HELIA_STATIONS].reverse() : HELIA_STATIONS;
    const heliaOrbitAngle = elapsedSeconds.value * 360 / HELIA_PERIOD;

    return stations.map((station, index) => {
        const center = station.slug === 'magnar'
            ? { x: 50, y: 50 }
            : planetPositions.value[station.slug];
        const planetIndex = Math.max(0, PLANET_ORBITS.findIndex(({ slug }) => slug === station.slug));
        const angle = (planetAngles.value[planetIndex] + heliaOrbitAngle) * Math.PI / 180;

        return {
            ...station,
            index,
            centerX: center.x,
            centerY: center.y,
            x: center.x + Math.sin(angle) * station.orbitRadius,
            y: center.y - Math.cos(angle) * station.orbitRadius,
        };
    });
});
const activeHeliaAgent = computed(() => heliaAgents.value[activeHeliaIndex.value]);
const heliaRoute = computed(() => {
    const agent = activeHeliaAgent.value;

    return [{
        ...agent,
        x: agent.centerX,
        y: agent.centerY,
        innerRadius: agent.orbitRadius - 1.5,
        outerRadius: agent.orbitRadius + 1.5,
    }];
});
const hoveredBodySlug = ref(null);
const activeBody = computed(() => props.celestialBodies[hoveredBodySlug.value] ?? null);
const heliaBrightness = computed(() => {
    const brightAngle = (brightSunStart + elapsedSeconds.value * 360 / BRIGHT_SUN_PERIOD) * Math.PI / 180;
    const eluminorPosition = {
        x: 50 + Math.sin(brightAngle) * 29,
        y: 50 - Math.cos(brightAngle) * 29,
    };
    const distance = Math.hypot(eluminorPosition.x - activeHeliaAgent.value.x, eluminorPosition.y - activeHeliaAgent.value.y);
    const proximity = Math.max(0, 1 - distance / 78);

    return (0.7 + proximity * 0.7).toFixed(2);
});

let animationFrame;
let motionReduced = false;
let lastFrameAt;

function orbitStyle(startAngle, period) {
    return {
        '--orbit-duration': `${period}s`,
        '--orbit-delay': `${-(period * startAngle) / 360}s`,
    };
}

function planetOrbitStyle(angle) {
    return { '--planet-angle': `${angle}deg` };
}

function heliaAgentStyle(agent) {
    return {
        '--helia-position-x': `${agent.x}%`,
        '--helia-position-y': `${agent.y}%`,
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

function toggleSimulation() {
    simulationPaused.value = !simulationPaused.value;

    if (motionReduced) {
        return;
    }

    if (simulationPaused.value) {
        window.cancelAnimationFrame(animationFrame);
        animationFrame = undefined;

        return;
    }

    lastFrameAt = undefined;
    animationFrame = window.requestAnimationFrame(updateYear);
}

function updateYear(now) {
    if (lastFrameAt !== undefined) {
        elapsedSeconds.value += (now - lastFrameAt) / 1000;
        yearPosition.value = (initialYearPosition + elapsedSeconds.value / SYNODIC_CYCLE_SECONDS) % 2;
    }

    lastFrameAt = now;
    animationFrame = window.requestAnimationFrame(updateYear);
}

onMounted(() => {
    motionReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (motionReduced) {
        return;
    }

    animationFrame = window.requestAnimationFrame(updateYear);
});

onUnmounted(() => {
    if (animationFrame) {
        window.cancelAnimationFrame(animationFrame);
    }
});
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
                    <span v-else class="button button-primary button-static">Sign up</span>
                    <a v-if="!authenticated" class="text-link" href="/login">Already have an account? Sign in</a>
                </div>
            </div>

            <div
                class="hero-art"
                :class="{ 'simulation-paused': simulationPaused }"
                role="group"
                aria-label="Geocentric model of Magnar, its moons, suns, and five neighboring planets"
            >
                <button
                    class="simulation-toggle"
                    type="button"
                    :aria-pressed="simulationPaused"
                    :aria-label="simulationPaused ? 'Resume celestial simulation' : 'Pause celestial simulation'"
                    @click="toggleSimulation"
                >
                    <span aria-hidden="true">{{ simulationPaused ? '▶' : 'Ⅱ' }}</span>
                    {{ simulationPaused ? 'Resume' : 'Pause' }}
                </button>
                <div
                    class="orbit-path moon-orbit moon-orbit-one"
                    :style="orbitStyle(moonStarts[0], MOON_PERIODS[0])"
                    :class="{ 'moon-disappears': isSecondCycle }"
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
                    :style="orbitStyle(moonStarts[1], MOON_PERIODS[1])"
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
                        <span class="moon moon-ambross" :class="{ 'is-half': ambrossIsHalf, 'is-autumn': isAutumn }">
                            <span class="moon-shadow"></span>
                        </span>
                        <span class="orbit-label moon-label ambross-label">{{ celestialBodies.ambross.name }}</span>
                    </a>
                </div>
                <div
                    class="orbit-path moon-orbit moon-orbit-three"
                    :style="orbitStyle(moonStarts[2], MOON_PERIODS[2])"
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
                    :style="orbitStyle(brightSunStart, BRIGHT_SUN_PERIOD)"
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
                <svg class="helia-route" viewBox="0 0 100 100" aria-hidden="true">
                    <template v-for="agent in heliaRoute" :key="agent.slug">
                        <circle
                            class="helia-route-strand helia-route-strand-one"
                            :cx="agent.x"
                            :cy="agent.y"
                            :r="agent.innerRadius"
                        ></circle>
                        <circle
                            class="helia-route-strand helia-route-strand-two"
                            :cx="agent.x"
                            :cy="agent.y"
                            :r="agent.outerRadius"
                        ></circle>
                    </template>
                </svg>
                <span
                    v-for="agent in heliaAgents"
                    :key="agent.slug"
                    class="helia-agent"
                    :class="{ 'helia-agent-active': agent.index === activeHeliaIndex }"
                    :style="heliaAgentStyle(agent)"
                    :aria-hidden="agent.index !== activeHeliaIndex"
                    :inert="agent.index !== activeHeliaIndex"
                >
                    <a
                        v-if="agent.index === activeHeliaIndex"
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
                    <span v-else class="sun sun-small helia-hidden-sun"></span>
                </span>
                <div
                    v-for="planet in planets"
                    :key="planet.slug"
                    class="orbit-path planet-orbit"
                    :class="`planet-orbit-${planet.slug}`"
                    :style="planetOrbitStyle(planet.angle)"
                >
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
                <span class="season-label">{{ season.name.toUpperCase() }} · CYCLE {{ season.cycle }}</span>
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
