<script setup>
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    query: {
        type: String,
        default: "",
    },
    results: {
        type: Array,
        default: () => [],
    },
    appDownloadUrl: {
        type: String,
        default: "",
    },
});

const appLogo = "/assets/images/applogo.png";
const searchInput = ref(props.query);

const resultMessage = computed(() => {
    if (!props.query.trim()) {
        return "Enter a place name to search destinations across Baao.";
    }

    if (props.results.length === 0) {
        return `No destinations found for "${props.query}".`;
    }

    return `${props.results.length} destination${
        props.results.length === 1 ? "" : "s"
    } found for "${props.query}"`;
});

function getLocationImage(location) {
    return location.gallery_images?.[0] ?? location.image_url ?? null;
}

function submitSearch() {
    const term = searchInput.value.trim();
    if (!term) {
        return;
    }

    router.get("/search", { q: term }, { preserveState: true });
}
</script>

<template>
    <Head :title="query ? `Search: ${query}` : 'Search Destinations'" />

    <div class="search-page">
        <header class="search-header">
            <div class="search-header-inner">
                <Link href="/" class="brand">
                    <img class="brand-icon" :src="appLogo" alt="i-Baao logo" />
                    <span class="brand-copy">
                        <strong>i-Baao</strong>
                        <small>Baao Tourism</small>
                    </span>
                </Link>

                <Link href="/" class="back-link">← Back to home</Link>
            </div>
        </header>

        <main class="search-main">
            <section class="search-hero">
                <p class="search-eyebrow">Search Results</p>
                <h1>Find a destination in Baao</h1>
                <p class="search-lead">{{ resultMessage }}</p>

                <form class="search-shell" @submit.prevent="submitSearch">
                    <span class="search-icon">⌕</span>
                    <input
                        v-model="searchInput"
                        type="search"
                        placeholder="Search places, resorts, restaurants..."
                    />
                    <button type="submit">Search</button>
                </form>
            </section>

            <section v-if="query.trim() && results.length" class="results-section">
                <div class="results-grid">
                    <article
                        v-for="location in results"
                        :key="location.id"
                        class="result-card"
                    >
                        <div
                            v-if="getLocationImage(location)"
                            class="result-image"
                            :style="{
                                backgroundImage: `url('${getLocationImage(location)}')`,
                            }"
                        ></div>
                        <div v-else class="result-image result-image-empty">
                            No image
                        </div>

                        <div class="result-body">
                            <span class="result-category">{{
                                location.category_label
                            }}</span>
                            <h2>{{ location.name }}</h2>
                            <p>{{ location.summary }}</p>

                            <div class="result-meta">
                                <span v-if="location.average_rating">
                                    ★ {{ location.average_rating.toFixed(1) }}
                                    <small
                                        >({{ location.ratings_count }}
                                        review{{
                                            location.ratings_count === 1
                                                ? ""
                                                : "s"
                                        }})</small
                                    >
                                </span>
                                <span v-else>No ratings yet</span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section
                v-else-if="query.trim()"
                class="empty-section"
            >
                <p>
                    We couldn't find any destinations matching your search. Try
                    another keyword or browse all places from the home page.
                </p>
                <Link href="/#destinations" class="browse-link">
                    Browse all destinations
                </Link>
            </section>

            <section class="app-help-section">
                <div class="app-help-card">
                    <img
                        class="app-help-logo"
                        :src="appLogo"
                        alt="i-Baao app logo"
                    />
                    <div class="app-help-copy">
                        <p class="app-help-eyebrow">Need directions?</p>
                        <h2>Get help reaching your destination</h2>
                        <p>
                            Download the official i-Baao app for turn-by-turn
                            navigation, offline destination guides, and real-time
                            updates to help you find and reach locations across
                            Baao with ease.
                        </p>
                    </div>
                    <a
                        v-if="appDownloadUrl"
                        :href="appDownloadUrl"
                        class="app-help-btn"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Download the App
                    </a>
                    <p v-else class="app-help-note">
                        The app download link will appear here once configured
                        in admin settings.
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap");

.search-page {
    --text-main: #0c2926;
    --text-muted: #5e7873;
    --accent-primary: #0f766e;
    --accent-soft: #14b8a6;
    --border: #dbe7e3;
    --shadow-sm: 0 4px 14px rgba(12, 41, 38, 0.06);

    min-height: 100vh;
    background: #ffffff;
    color: var(--text-main);
    font-family: "Plus Jakarta Sans", sans-serif;
}

.search-header {
    border-bottom: 1px solid var(--border);
    background: #ffffff;
}

.search-header-inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--text-main);
}

.brand-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    object-fit: cover;
}

.brand-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.brand-copy strong {
    font-family: "Outfit", sans-serif;
    font-size: 1rem;
}

.brand-copy small {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.back-link {
    color: var(--accent-primary);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 700;
}

.back-link:hover {
    color: var(--accent-soft);
}

.search-main {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2.5rem 1.25rem 4rem;
}

.search-hero {
    margin-bottom: 2.5rem;
}

.search-eyebrow {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--accent-primary);
    margin-bottom: 0.5rem;
}

.search-hero h1 {
    font-family: "Outfit", sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
}

.search-lead {
    margin: 0.75rem 0 1.5rem;
    color: var(--text-muted);
    line-height: 1.6;
}

.search-shell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    max-width: 36rem;
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 0.45rem 0.45rem 0.45rem 1.15rem;
    box-shadow: var(--shadow-sm);
}

.search-icon {
    font-size: 1.3rem;
    color: var(--accent-primary);
}

.search-shell input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 1rem;
    font-family: inherit;
    color: var(--text-main);
    min-width: 0;
}

.search-shell button {
    border: none;
    cursor: pointer;
    padding: 0.7rem 1.6rem;
    border-radius: 999px;
    background: linear-gradient(135deg, var(--accent-primary), var(--accent-soft));
    color: #ffffff;
    font-weight: 700;
    font-family: inherit;
}

.results-section {
    margin-bottom: 3rem;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}

.result-card {
    border: 1px solid var(--border);
    border-radius: 1rem;
    overflow: hidden;
    background: #ffffff;
    box-shadow: var(--shadow-sm);
}

.result-image {
    height: 180px;
    background-size: cover;
    background-position: center;
}

.result-image-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8faf9;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 600;
}

.result-body {
    padding: 1.1rem 1.15rem 1.25rem;
}

.result-category {
    display: inline-block;
    margin-bottom: 0.45rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: rgba(15, 118, 110, 0.1);
    color: var(--accent-primary);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.result-body h2 {
    font-family: "Outfit", sans-serif;
    font-size: 1.15rem;
    margin: 0 0 0.5rem;
}

.result-body p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.92rem;
    line-height: 1.6;
}

.result-meta {
    margin-top: 0.85rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-main);
}

.result-meta small {
    font-weight: 600;
    color: var(--text-muted);
}

.empty-section {
    margin-bottom: 3rem;
    padding: 2rem;
    border: 1px dashed var(--border);
    border-radius: 1rem;
    text-align: center;
    color: var(--text-muted);
}

.browse-link {
    display: inline-block;
    margin-top: 1rem;
    color: var(--accent-primary);
    font-weight: 700;
    text-decoration: none;
}

.app-help-section {
    margin-top: 1rem;
}

.app-help-card {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1.25rem 1.5rem;
    align-items: center;
    padding: 1.75rem;
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    background: linear-gradient(135deg, #042f2e 0%, #0f766e 100%);
    color: #ffffff;
}

.app-help-logo {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    object-fit: cover;
}

.app-help-eyebrow {
    margin: 0 0 0.35rem;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.75);
}

.app-help-copy h2 {
    font-family: "Outfit", sans-serif;
    margin: 0 0 0.5rem;
    font-size: 1.35rem;
}

.app-help-copy p {
    margin: 0;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.65;
    font-size: 0.95rem;
}

.app-help-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 1.25rem;
    border-radius: 999px;
    background: #ffffff;
    color: var(--accent-primary);
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
}

.app-help-note {
    margin: 0;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.75);
}

@media (max-width: 768px) {
    .search-header-inner {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-shell {
        max-width: 100%;
    }

    .search-shell button {
        padding: 0.7rem 1.1rem;
    }

    .app-help-card {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .app-help-logo {
        margin: 0 auto;
    }

    .app-help-btn {
        width: 100%;
    }
}
</style>
