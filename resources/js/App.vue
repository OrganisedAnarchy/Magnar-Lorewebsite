<script setup>
defineProps({
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
});
</script>

<template>
    <main class="landing">
        <header class="site-header">
            <a class="wordmark" href="/" aria-label="Magnar Lore home">
                <span class="wordmark-mark" aria-hidden="true">M</span>
                <span>MAGNAR <span class="wordmark-light">LORE</span></span>
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
                <p class="eyebrow"><span class="eyebrow-line"></span> A home for worlds imagined</p>
                <h1 id="hero-title">Every world has<br>a story <em>worth telling.</em></h1>
                <p class="hero-description">
                    Gather the histories, characters, places, and little details of the worlds you create.
                    Your lore begins here.
                </p>
                <div class="hero-actions">
                    <a v-if="!authenticated" class="button button-primary" href="/register">
                        Begin your chronicle <span aria-hidden="true">↗</span>
                    </a>
                    <span v-else class="button button-primary button-static">Your chronicle starts here</span>
                    <a v-if="!authenticated" class="text-link" href="/login">Already have an account? Sign in</a>
                </div>
                <p class="hero-note">A quiet corner of the universe, made by you.</p>
            </div>

            <div class="hero-art" aria-hidden="true">
                <div class="orbit orbit-outer"></div>
                <div class="orbit orbit-inner"></div>
                <div class="orbit orbit-dash"></div>
                <div class="planet planet-large"></div>
                <div class="planet planet-small"></div>
                <div class="planet planet-tiny"></div>
                <span class="art-star art-star-one">✳</span>
                <span class="art-star art-star-two">✦</span>
                <span class="art-caption">THE UNWRITTEN<br>IS YOURS</span>
            </div>
        </section>

        <footer class="site-footer">
            <span>MAGNAR LORE</span>
            <span>Make room for wonder.</span>
        </footer>
    </main>
</template>
