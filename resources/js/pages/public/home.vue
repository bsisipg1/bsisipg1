<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    locations: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [{ key: "all", label: "All" }],
    },
    reviewSummary: {
        type: Object,
        default: () => ({
            average_rating: null,
            total_reviews: 0,
            breakdown: [],
        }),
    },
    recentReviews: {
        type: Array,
        default: () => [],
    },
    events: {
        type: Array,
        default: () => [],
    },
    appDownloadUrl: {
        type: String,
        default: "",
    },
});

const appLogo = "/assets/images/applogo.png";

const mobileAppFeatures = [
    {
        icon: "⌖",
        title: "Interactive Maps",
        description:
            "Browse attractions, restaurants, and hidden gems with the same map experience on your phone.",
    },
    {
        icon: "↗",
        title: "Get Directions",
        description:
            "Find your way to destinations across Baao with quick navigation from any location.",
    },
    {
        icon: "★",
        title: "Discover More",
        description:
            "Explore events, culture, food spots, and resorts curated for visitors and locals.",
    },
    {
        icon: "⬇",
        title: "Easy Download",
        description:
            "Install the official Android app in minutes and start exploring Baao on the go.",
    },
];

const navLinks = [
    { name: "Home", href: "/" },
    { name: "Destinations", href: "#destinations" },
    { name: "Events", href: "#events" },
    { name: "About", href: "#about" },
];

const searchTerm = ref("");
const typingPlaceholder = ref("Search places, resorts, restaurants...");
const searchInputFocused = ref(false);
let typingTimer = null;
const activeCategory = ref("all");
const selectedDestinationId = ref(props.locations[0]?.id ?? null);
const mobileMenuOpen = ref(false);
const navScrolled = ref(false);
const downloadsOpen = ref(false);
const downloadsDropdownRef = ref(null);
const showDownloadModal = ref(false);

const filteredDestinations = computed(() => {
    return props.locations.filter((destination) => {
        return (
            activeCategory.value === "all" ||
            destination.category === activeCategory.value
        );
    });
});

const activeDestination = computed(() => {
    return (
        filteredDestinations.value.find(
            (destination) => destination.id === selectedDestinationId.value,
        ) ??
        filteredDestinations.value[0] ??
        props.locations[0] ??
        null
    );
});

const activeDestinationIndex = computed(() => {
    const index = filteredDestinations.value.findIndex(
        (destination) => destination.id === selectedDestinationId.value,
    );

    return index >= 0 ? index : 0;
});

const destinationSwipeDeltaX = ref(0);
const isDestinationDragging = ref(false);
const destinationSwipeStartX = ref(0);
const destinationSwipeThreshold = 70;
let destinationMouseMoveHandler = null;
let destinationMouseUpHandler = null;

const resultMessage = computed(() => {
    if (props.locations.length === 0) {
        return "No destinations available yet";
    }

    return `${props.locations.length} destination${
        props.locations.length === 1 ? "" : "s"
    } across Baao`;
});

const averageRating = computed(
    () =>
        activeDestination.value?.average_rating ??
        props.reviewSummary.average_rating ??
        null,
);

const featuredDestinations = computed(() => props.locations.slice(0, 6));

const searchableLocationNames = computed(() =>
    [
        ...new Set(
            props.locations
                .map((location) => location.name?.trim())
                .filter(Boolean),
        ),
    ],
);

function stopTypingAnimation() {
    if (typingTimer !== null) {
        clearTimeout(typingTimer);
        typingTimer = null;
    }
}

function startTypingAnimation() {
    stopTypingAnimation();

    const names = searchableLocationNames.value;

    if (searchInputFocused.value || searchTerm.value.trim()) {
        return;
    }

    if (names.length === 0) {
        typingPlaceholder.value = "Search places, resorts, restaurants...";
        return;
    }

    let nameIndex = 0;
    let charIndex = 0;
    let deleting = false;

    const run = () => {
        if (searchInputFocused.value || searchTerm.value.trim()) {
            stopTypingAnimation();
            return;
        }

        const current = names[nameIndex % names.length];
        const prefix = "Search ";

        if (!deleting) {
            charIndex += 1;
            typingPlaceholder.value = `${prefix}${current.slice(0, charIndex)}...`;

            if (charIndex >= current.length) {
                deleting = true;
                typingTimer = window.setTimeout(run, 1800);
            } else {
                typingTimer = window.setTimeout(run, 85);
            }

            return;
        }

        charIndex -= 1;

        if (charIndex <= 0) {
            deleting = false;
            nameIndex += 1;
            typingPlaceholder.value = `${prefix}...`;
            typingTimer = window.setTimeout(run, 450);
            return;
        }

        typingPlaceholder.value = `${prefix}${current.slice(0, charIndex)}...`;
        typingTimer = window.setTimeout(run, 45);
    };

    typingPlaceholder.value = "Search ...";
    typingTimer = window.setTimeout(run, 500);
}

function handleSearchFocus() {
    searchInputFocused.value = true;
    stopTypingAnimation();
    typingPlaceholder.value = "Search places, resorts, restaurants...";
}

function handleSearchBlur() {
    searchInputFocused.value = false;
    startTypingAnimation();
}

const heroStats = computed(() => [
    {
        value: props.locations.length,
        label: props.locations.length === 1 ? "Destination" : "Destinations",
    },
    {
        value: props.categories.filter((category) => category.key !== "all")
            .length,
        label: "Categories",
    },
    {
        value: props.events.length,
        label: props.events.length === 1 ? "Event" : "Events",
    },
    {
        value: props.reviewSummary.average_rating
            ? props.reviewSummary.average_rating.toFixed(1)
            : "—",
        label: "Avg. Rating",
    },
]);

function submitSearch() {
    const query = searchTerm.value.trim();
    if (!query) {
        return;
    }

    router.get("/search", { q: query });
}

function formatCoordinates(latitude, longitude) {
    return `${Number(latitude).toFixed(5)}, ${Number(longitude).toFixed(5)}`;
}

function getDestinationImages(destination) {
    if (!destination) {
        return [];
    }

    const images = [
        destination.image_url,
        ...(destination.gallery_images ?? []),
    ].filter(Boolean);

    return [...new Set(images)];
}

function getFeaturedGalleryImages(destination) {
    return getDestinationImages(destination).slice(0, 3);
}

function getExtraGalleryImages(destination) {
    return getDestinationImages(destination).slice(3);
}

function galleryLayoutClass(destination) {
    const count = getDestinationImages(destination).length;

    return {
        "gallery-single": count === 1,
        "gallery-duo": count === 2,
        "gallery-multi": count >= 3,
    };
}

function syncSelectionAfterFilter() {
    if (filteredDestinations.value.length === 0) {
        return;
    }

    const hasSelected = filteredDestinations.value.some(
        (destination) => destination.id === selectedDestinationId.value,
    );

    if (!hasSelected) {
        selectedDestinationId.value = filteredDestinations.value[0].id;
    }
}

function selectCategory(categoryKey) {
    activeCategory.value = categoryKey;
    syncSelectionAfterFilter();
}

function selectDestination(destinationId) {
    selectedDestinationId.value = destinationId;
}

function exploreDestination(destinationId) {
    selectDestination(destinationId);

    nextTick(() => {
        document
            .getElementById("destinations")
            ?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
}

function goToPreviousDestination() {
    const list = filteredDestinations.value;

    if (list.length <= 1) {
        return;
    }

    const nextIndex =
        activeDestinationIndex.value <= 0
            ? list.length - 1
            : activeDestinationIndex.value - 1;

    selectDestination(list[nextIndex].id);
}

function goToNextDestination() {
    const list = filteredDestinations.value;

    if (list.length <= 1) {
        return;
    }

    const nextIndex =
        activeDestinationIndex.value >= list.length - 1
            ? 0
            : activeDestinationIndex.value + 1;

    selectDestination(list[nextIndex].id);
}

function finishDestinationSwipe() {
    if (!isDestinationDragging.value) {
        return;
    }

    if (destinationSwipeDeltaX.value <= -destinationSwipeThreshold) {
        goToNextDestination();
    } else if (destinationSwipeDeltaX.value >= destinationSwipeThreshold) {
        goToPreviousDestination();
    }

    isDestinationDragging.value = false;
    destinationSwipeDeltaX.value = 0;
}

function handleDestinationTouchStart(event) {
    if (
        filteredDestinations.value.length <= 1 ||
        window.matchMedia("(min-width: 769px)").matches
    ) {
        return;
    }

    isDestinationDragging.value = true;
    destinationSwipeStartX.value = event.touches[0].clientX;
    destinationSwipeDeltaX.value = 0;
}

function handleDestinationTouchMove(event) {
    if (!isDestinationDragging.value) {
        return;
    }

    destinationSwipeDeltaX.value =
        event.touches[0].clientX - destinationSwipeStartX.value;
}

function handleDestinationTouchEnd() {
    finishDestinationSwipe();
}

function handleDestinationMouseDown(event) {
    if (
        filteredDestinations.value.length <= 1 ||
        event.button !== 0 ||
        window.matchMedia("(min-width: 769px)").matches
    ) {
        return;
    }

    isDestinationDragging.value = true;
    destinationSwipeStartX.value = event.clientX;
    destinationSwipeDeltaX.value = 0;

    destinationMouseMoveHandler = (moveEvent) => {
        if (!isDestinationDragging.value) {
            return;
        }

        destinationSwipeDeltaX.value =
            moveEvent.clientX - destinationSwipeStartX.value;
    };

    destinationMouseUpHandler = () => {
        finishDestinationSwipe();
        document.removeEventListener("mousemove", destinationMouseMoveHandler);
        document.removeEventListener("mouseup", destinationMouseUpHandler);
        destinationMouseMoveHandler = null;
        destinationMouseUpHandler = null;
    };

    document.addEventListener("mousemove", destinationMouseMoveHandler);
    document.addEventListener("mouseup", destinationMouseUpHandler);
}

watch(searchTerm, () => {
    if (searchTerm.value.trim()) {
        stopTypingAnimation();
    } else if (!searchInputFocused.value) {
        startTypingAnimation();
    }
});

watch(searchableLocationNames, () => {
    if (!searchInputFocused.value && !searchTerm.value.trim()) {
        startTypingAnimation();
    }
});

function handleScroll() {
    navScrolled.value = window.scrollY > 80;
}

function toggleDownloads() {
    downloadsOpen.value = !downloadsOpen.value;
}

function openDownloadModal() {
    showDownloadModal.value = true;
}

function closeDownloadModal() {
    showDownloadModal.value = false;
}

function handleDownloadModalKeydown(event) {
    if (event.key === "Escape") {
        closeDownloadModal();
    }
}

function handleDocumentClick(event) {
    if (
        downloadsDropdownRef.value &&
        !downloadsDropdownRef.value.contains(event.target)
    ) {
        downloadsOpen.value = false;
    }
}

onMounted(() => {
    if (window.location.hash === "#home") {
        history.replaceState(null, "", window.location.pathname);
    }

    window.addEventListener("scroll", handleScroll, { passive: true });
    document.addEventListener("click", handleDocumentClick);
    document.addEventListener("keydown", handleDownloadModalKeydown);
    handleScroll();
    startTypingAnimation();
});

onBeforeUnmount(() => {
    if (destinationMouseMoveHandler) {
        document.removeEventListener("mousemove", destinationMouseMoveHandler);
    }
    if (destinationMouseUpHandler) {
        document.removeEventListener("mouseup", destinationMouseUpHandler);
    }

    window.removeEventListener("scroll", handleScroll);
    document.removeEventListener("click", handleDocumentClick);
    document.removeEventListener("keydown", handleDownloadModalKeydown);
    stopTypingAnimation();
});
</script>

<template>
    <div class="tourism-page">
        <header
            class="top-nav-shell"
            :class="{
                'nav-scrolled': navScrolled,
                'mobile-menu-open': mobileMenuOpen,
            }"
        >
            <div class="top-nav">
                <a class="brand" href="/">
                    <img class="brand-icon" :src="appLogo" alt="i-Baao logo" />
                    <span class="brand-copy">
                        <strong>Explore Baao</strong>
                        <small>Tourism Mapping System</small>
                    </span>
                </a>

                <nav class="nav-links" aria-label="Primary navigation">
                    <a
                        v-for="link in navLinks"
                        :key="link.name"
                        :href="link.href"
                        class="nav-link"
                    >
                        {{ link.name }}
                    </a>
                </nav>

                <div class="auth-actions">
                    <div
                        v-if="appDownloadUrl"
                        ref="downloadsDropdownRef"
                        class="downloads-dropdown"
                    >
                        <button
                            type="button"
                            class="downloads-trigger"
                            :aria-expanded="downloadsOpen"
                            aria-haspopup="true"
                            @click.stop="toggleDownloads"
                        >
                            Get the App
                            <span
                                class="downloads-chevron"
                                :class="{ open: downloadsOpen }"
                            ></span>
                        </button>
                        <Transition name="dropdown">
                            <div
                                v-if="downloadsOpen"
                                class="downloads-menu"
                                role="menu"
                            >
                                <a
                                    :href="appDownloadUrl"
                                    class="downloads-item"
                                    role="menuitem"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    @click="downloadsOpen = false"
                                >
                                    <img
                                        class="downloads-item-icon"
                                        :src="appLogo"
                                        alt=""
                                    />
                                    <span class="downloads-item-copy">
                                        <strong>i-Baao Android App</strong>
                                        <small>Free download</small>
                                    </span>
                                </a>
                            </div>
                        </Transition>
                    </div>
                    <Link href="/login" class="ghost-btn">Login</Link>
                </div>

                <button
                    type="button"
                    class="hamburger-btn"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    aria-label="Toggle menu"
                >
                    <span :class="{ open: mobileMenuOpen }"></span>
                    <span :class="{ open: mobileMenuOpen }"></span>
                    <span :class="{ open: mobileMenuOpen }"></span>
                </button>
            </div>

            <Transition name="mobile-menu">
                <nav
                    v-if="mobileMenuOpen"
                    class="mobile-nav"
                    aria-label="Mobile navigation"
                >
                    <a
                        v-for="link in navLinks"
                        :key="'mobile-' + link.name"
                        :href="link.href"
                        class="mobile-nav-link"
                        @click="mobileMenuOpen = false"
                    >
                        {{ link.name }}
                    </a>
                    <div v-if="appDownloadUrl" class="mobile-download-card">
                        <p class="mobile-section-label">Get the App</p>
                        <a
                            :href="appDownloadUrl"
                            class="mobile-download-link"
                            target="_blank"
                            rel="noopener noreferrer"
                            @click="mobileMenuOpen = false"
                        >
                            <img
                                class="mobile-download-icon"
                                :src="appLogo"
                                alt=""
                            />
                            <span class="mobile-download-copy">
                                <strong>Download i-Baao App</strong>
                                <small>Free for Android</small>
                            </span>
                            <span class="mobile-download-arrow">↓</span>
                        </a>
                    </div>
                    <div class="mobile-auth">
                        <Link
                            href="/login"
                            class="mobile-login-btn"
                            @click="mobileMenuOpen = false"
                        >
                            Login
                        </Link>
                    </div>
                </nav>
            </Transition>
        </header>

        <section class="hero-section">
            <div class="hero-inner">
                <!-- Left: text content -->
                <div class="hero-content">
                    <p class="hero-pill">
                        <span class="hero-pill-dot"></span>
                        Baao, Camarines Sur
                    </p>
                    <h1>
                        Discover the
                        <span>Heart of Baao</span>
                    </h1>
                    <p class="hero-subtitle">
                        Your gateway to the best of Baao — from serene lakeside
                        views and heritage landmarks to local food spots and hidden
                        resorts. Plan your trip and start exploring.
                    </p>

                    <form class="search-shell" @submit.prevent="submitSearch">
                        <span class="search-icon">⌕</span>
                        <input
                            v-model="searchTerm"
                            type="search"
                            :placeholder="typingPlaceholder"
                            @focus="handleSearchFocus"
                            @blur="handleSearchBlur"
                        />
                        <button type="submit">Search</button>
                    </form>

                    <p class="search-result">{{ resultMessage }}</p>

                    <dl class="hero-stats">
                        <div
                            v-for="stat in heroStats"
                            :key="stat.label"
                            class="hero-stat"
                        >
                            <dt>{{ stat.value }}</dt>
                            <dd>{{ stat.label }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Right: traveler image (studio white-bg, blends into honeycomb via multiply) -->
                <div class="hero-visual">
                    <img
                        src="/assets/images/herosection.png"
                        alt="Woman traveler ready to explore"
                        class="hero-traveler-img"
                    />
                </div>
            </div>

        </section>

        <main class="main-content">
            <!-- WHY VISIT BAAO -->
            <section class="why-visit-section">
                <div class="section-shell">
                    <p class="section-pill section-pill-mint">Why Visit</p>
                    <h2>Experience Baao Like Never Before</h2>
                    <p class="section-subtitle">
                        A hidden gem in Camarines Sur — rich in nature, heritage, and warm Filipino hospitality
                    </p>
                    <div class="why-list">
                        <div class="why-item">
                            <span class="why-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7c3-2 6-2 9 0s6 2 9 0"/><path d="M3 12c3-2 6-2 9 0s6 2 9 0"/><path d="M3 17c3-2 6-2 9 0s6 2 9 0"/></svg>
                            </span>
                            <div class="why-text">
                                <h3>Natural Wonders</h3>
                                <p>Lakeside views at Lake Buhi, scenic river trails, and lush countryside — Baao's landscapes are breathtaking and largely undiscovered.</p>
                            </div>
                        </div>
                        <div class="why-item">
                            <span class="why-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </span>
                            <div class="why-text">
                                <h3>Rich Heritage</h3>
                                <p>Centuries of history await at Baao's heritage churches, ancestral homes, and cultural landmarks that carry the town's proud story.</p>
                            </div>
                        </div>
                        <div class="why-item">
                            <span class="why-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                            </span>
                            <div class="why-text">
                                <h3>Local Cuisine</h3>
                                <p>Authentic Bicolano flavors — Laing, Bicol Express, and freshwater fish dishes unique to the riverside markets of Baao.</p>
                            </div>
                        </div>
                        <div class="why-item">
                            <span class="why-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                            <div class="why-text">
                                <h3>Festivals & Culture</h3>
                                <p>Vibrant local festivals, traditional celebrations, and community events that showcase the heart and soul of Baao's people.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- EXPLORE BY CATEGORY -->
            <section class="category-explore-section section-shell">
                <p class="section-pill section-pill-blue">Explore</p>
                <h2>Browse by Category</h2>
                <p class="section-subtitle">Find exactly what you're looking for — nature, food, heritage, and more</p>
                <div class="cat-explore-grid">
                    <button
                        v-for="category in categories.filter(c => c.key !== 'all')"
                        :key="category.key"
                        type="button"
                        class="cat-explore-card"
                        @click="selectCategory(category.key); $nextTick(() => document.getElementById('destinations')?.scrollIntoView({ behavior: 'smooth' }))"
                    >
                        <span class="cat-explore-icon">
                            <span v-if="category.key === 'nature'">🌿</span>
                            <span v-else-if="category.key === 'culture'">🎭</span>
                            <span v-else-if="category.key === 'food'">🍽️</span>
                            <span v-else-if="category.key === 'resorts'">🏨</span>
                            <span v-else-if="category.key === 'religious'">⛪</span>
                            <span v-else-if="category.key === 'historical'">🏛️</span>
                            <span v-else-if="category.key === 'parks'">🌳</span>
                            <span v-else-if="category.key === 'lakes_water'">💧</span>
                            <span v-else-if="category.key === 'landmarks'">📍</span>
                            <span v-else-if="category.key === 'markets'">🛒</span>
                            <span v-else-if="category.key === 'adventure'">🧗</span>
                            <span v-else-if="category.key === 'museums'">🖼️</span>
                            <span v-else-if="category.key === 'accommodation'">🏩</span>
                            <span v-else-if="category.key === 'entertainment'">🎪</span>
                            <span v-else-if="category.key === 'wellness'">🧘</span>
                            <span v-else-if="category.key === 'shopping'">🛍️</span>
                            <span v-else>📌</span>
                        </span>
                        <span class="cat-explore-label">{{ category.label }}</span>
                        <span class="cat-explore-count">
                            {{ locations.filter(l => l.category === category.key).length }} spot{{ locations.filter(l => l.category === category.key).length !== 1 ? 's' : '' }}
                        </span>
                    </button>
                </div>
            </section>

            <!-- TRAVEL TIPS -->
            <section class="travel-tips-section section-shell">
                <p class="section-pill section-pill-gold">Visitor Guide</p>
                <h2>Travel Tips for Baao</h2>
                <p class="section-subtitle">Plan your trip better with these quick essentials</p>
                <div class="tips-list">
                    <div class="tips-col">
                        <div class="tip-item">
                            <span class="tip-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </span>
                            <div>
                                <h4>Best Time to Visit</h4>
                                <p>November to May — dry season means clear skies, perfect for outdoor exploration and lake activities.</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                            </span>
                            <div>
                                <h4>Getting There</h4>
                                <p>Bus from Manila to Naga City (8–10 hrs), then jeepney or tricycle to Baao — about 30 minutes from Naga.</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <div>
                                <h4>Budget Guide</h4>
                                <p>Most attractions are free or low-cost. Expect ₱500–₱1,500/day for food and transport.</p>
                            </div>
                        </div>
                    </div>
                    <div class="tips-divider"></div>
                    <div class="tips-col">
                        <div class="tip-item">
                            <span class="tip-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            </span>
                            <div>
                                <h4>Use the i-Baao App</h4>
                                <p>Download the official i-Baao app for offline guides, navigation, and real-time event updates.</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 17.58A5 5 0 0 0 18 8h-1.26A8 8 0 1 0 4 16.25"/><line x1="8" y1="16" x2="8.01" y2="16"/><line x1="8" y1="20" x2="8.01" y2="20"/><line x1="12" y1="18" x2="12.01" y2="18"/><line x1="12" y1="22" x2="12.01" y2="22"/><line x1="16" y1="16" x2="16.01" y2="16"/><line x1="16" y1="20" x2="16.01" y2="20"/></svg>
                            </span>
                            <div>
                                <h4>Weather & Clothing</h4>
                                <p>Tropical climate — light clothing, sunscreen, and a rain jacket for June–October rainy season.</p>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <div>
                                <h4>Local Etiquette</h4>
                                <p>"Magandang araw" goes a long way. Always ask permission before photographing locals.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="section-shell destination-section"
                id="destinations"
            >
                <p class="section-pill section-pill-mint">
                    Destination Details
                </p>
                <h2>Take a Closer Look</h2>
                <p class="section-subtitle">
                    Browse photos, ratings, and key details for each place
                </p>

                <div
                    v-if="filteredDestinations.length"
                    class="destination-swipe-shell"
                >
                    <div class="destination-swipe-meta">
                        <p class="destination-swipe-count">
                            {{ activeDestinationIndex + 1 }} /
                            {{ filteredDestinations.length }}
                        </p>
                        <p
                            v-if="filteredDestinations.length > 1"
                            class="destination-swipe-hint destination-swipe-hint-mobile"
                        >
                            Swipe to explore locations
                        </p>
                    </div>

                    <div class="destination-swipe-stage">
                        <button
                            v-if="filteredDestinations.length > 1"
                            type="button"
                            class="destination-swipe-arrow destination-swipe-arrow-prev"
                            aria-label="Previous destination"
                            @click="goToPreviousDestination"
                        >
                            ←
                        </button>

                        <div
                            class="destination-swipe-viewport"
                            @touchstart.passive="handleDestinationTouchStart"
                            @touchmove="handleDestinationTouchMove"
                            @touchend="handleDestinationTouchEnd"
                            @mousedown="handleDestinationMouseDown"
                        >
                            <div
                                class="destination-swipe-track"
                                :class="{ dragging: isDestinationDragging }"
                                :style="{
                                    transform: `translateX(calc(-${activeDestinationIndex * 100}% + ${destinationSwipeDeltaX}px))`,
                                }"
                            >
                                <article
                                    v-for="destination in filteredDestinations"
                                    :key="destination.id"
                                    class="destination-slide"
                                >
                                    <div class="destination-layout">
                                        <div
                                            class="destination-gallery"
                                            :class="
                                                galleryLayoutClass(destination)
                                            "
                                        >
                                            <div
                                                v-if="
                                                    getFeaturedGalleryImages(
                                                        destination,
                                                    )[0]
                                                "
                                                class="main-photo has-image"
                                                :style="{
                                                    backgroundImage: `url('${getFeaturedGalleryImages(destination)[0]}')`,
                                                }"
                                            ></div>
                                            <div
                                                v-else
                                                class="main-photo gallery-placeholder"
                                            >
                                                No image available
                                            </div>

                                            <div
                                                v-if="
                                                    getFeaturedGalleryImages(
                                                        destination,
                                                    )[1]
                                                "
                                                class="side-photo side-photo-top has-image"
                                                :style="{
                                                    backgroundImage: `url('${getFeaturedGalleryImages(destination)[1]}')`,
                                                }"
                                            ></div>

                                            <div
                                                v-if="
                                                    getFeaturedGalleryImages(
                                                        destination,
                                                    )[2]
                                                "
                                                class="side-photo side-photo-bottom has-image"
                                                :style="{
                                                    backgroundImage: `url('${getFeaturedGalleryImages(destination)[2]}')`,
                                                }"
                                            ></div>

                                            <div
                                                v-if="
                                                    getExtraGalleryImages(
                                                        destination,
                                                    ).length
                                                "
                                                class="gallery-extra"
                                            >
                                                <div
                                                    v-for="(
                                                        image, index
                                                    ) in getExtraGalleryImages(
                                                        destination,
                                                    )"
                                                    :key="`${destination.id}-gallery-${index}`"
                                                    class="gallery-thumb has-image"
                                                    :style="{
                                                        backgroundImage: `url('${image}')`,
                                                    }"
                                                    :title="`${destination.name} gallery photo ${index + 4}`"
                                                ></div>
                                            </div>
                                        </div>

                                        <div class="destination-side">
                                            <article class="spot-card">
                                                <div class="spot-card-head">
                                                    <div>
                                                        <h3>
                                                            {{
                                                                destination.name
                                                            }}
                                                        </h3>
                                                        <p>
                                                            {{
                                                                destination.category_label
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="spot-card-icons">
                                                        <button
                                                            type="button"
                                                            aria-label="Save to favorites"
                                                            @click="openDownloadModal"
                                                        >
                                                            ♡
                                                        </button>
                                                        <button
                                                            type="button"
                                                            aria-label="Open location"
                                                            @click="openDownloadModal"
                                                        >
                                                            ↗
                                                        </button>
                                                    </div>
                                                </div>

                                                <p
                                                    v-if="
                                                        destination.average_rating
                                                    "
                                                    class="spot-rating"
                                                >
                                                    <span
                                                        >★
                                                        {{
                                                            destination.average_rating.toFixed(
                                                                1,
                                                            )
                                                        }}</span
                                                    >
                                                    ({{
                                                        destination.ratings_count
                                                    }}
                                                    reviews)
                                                </p>
                                                <p
                                                    v-else
                                                    class="spot-rating spot-rating-empty"
                                                >
                                                    No ratings yet
                                                </p>

                                                <p class="spot-description">
                                                    {{ destination.description }}
                                                </p>

                                                <div class="spot-actions">
                                                    <button
                                                        type="button"
                                                        class="solid-btn"
                                                        @click="openDownloadModal"
                                                    >
                                                        Get Directions
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="ghost-pill"
                                                        @click="openDownloadModal"
                                                    >
                                                        Save
                                                    </button>
                                                </div>
                                            </article>

                                            <article class="info-card">
                                                <div class="info-tabs">
                                                    <button
                                                        type="button"
                                                        class="active"
                                                    >
                                                        Information
                                                    </button>
                                                </div>

                                                <ul>
                                                    <li>
                                                        <strong>Category</strong>
                                                        <span>{{
                                                            destination.category_label
                                                        }}</span>
                                                    </li>
                                                    <li>
                                                        <strong
                                                            >Municipality</strong
                                                        >
                                                        <span
                                                            >Baao, Camarines
                                                            Sur</span
                                                        >
                                                    </li>
                                                    <li>
                                                        <strong
                                                            >Coordinates</strong
                                                        >
                                                        <span>{{
                                                            formatCoordinates(
                                                                destination.latitude,
                                                                destination.longitude,
                                                            )
                                                        }}</span>
                                                    </li>
                                                </ul>
                                            </article>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <button
                            v-if="filteredDestinations.length > 1"
                            type="button"
                            class="destination-swipe-arrow destination-swipe-arrow-next"
                            aria-label="Next destination"
                            @click="goToNextDestination"
                        >
                            →
                        </button>
                    </div>

                    <div
                        v-if="filteredDestinations.length > 1"
                        class="destination-swipe-dots"
                        aria-hidden="true"
                    >
                        <span
                            v-for="(_, index) in filteredDestinations"
                            :key="index"
                            class="destination-swipe-dot"
                            :class="{
                                active: index === activeDestinationIndex,
                            }"
                        ></span>
                    </div>
                </div>

                <p v-else class="section-empty">
                    Add locations in the admin panel to showcase destinations
                    here.
                </p>
            </section>

            <section class="app-section">
                <div class="mobile-app-feature">
                    <div class="mobile-app-intro">
                        <p class="section-pill section-pill-blue">Mobile App</p>
                        <h3>Introducing the i-Baao App</h3>
                        <p>
                            Take Baao tourism with you wherever you go. The
                            official mobile app brings the interactive map,
                            destinations, and local highlights right to your
                            fingertips.
                        </p>
                        <a
                            v-if="appDownloadUrl"
                            :href="appDownloadUrl"
                            class="solid-btn mobile-app-download-btn"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Download Android App
                        </a>
                    </div>

                    <ul class="mobile-app-feature-list">
                        <li
                            v-for="feature in mobileAppFeatures"
                            :key="feature.title"
                        >
                            <span class="mobile-app-feature-icon">{{
                                feature.icon
                            }}</span>
                            <div>
                                <strong>{{ feature.title }}</strong>
                                <p>{{ feature.description }}</p>
                            </div>
                        </li>
                    </ul>

                    <div class="mobile-app-preview">
                        <img
                            class="mobile-app-preview-logo"
                            :src="appLogo"
                            alt="i-Baao app logo"
                        />
                        <strong>i-Baao</strong>
                        <small>Official Tourism App</small>
                        <span class="mobile-app-version">Version 2</span>
                    </div>
                </div>
            </section>

            <section class="section-shell reviews-section">
                <p class="section-pill section-pill-gold">
                    Ratings &amp; Reviews
                </p>
                <h2>What Visitors Say</h2>
                <p class="section-subtitle">
                    Read authentic reviews from travelers who have experienced
                    Baao
                </p>

                <div
                    v-if="reviewSummary.total_reviews > 0"
                    class="reviews-layout"
                >
                    <aside class="rating-panel">
                        <h3>
                            {{ reviewSummary.average_rating?.toFixed(1) ?? "—" }}
                        </h3>
                        <p class="stars">★★★★★</p>
                        <small
                            >Based on
                            {{ reviewSummary.total_reviews }} review{{
                                reviewSummary.total_reviews === 1 ? "" : "s"
                            }}</small
                        >

                        <ul>
                            <li
                                v-for="item in reviewSummary.breakdown"
                                :key="item.stars"
                            >
                                <span>{{ item.stars }}★</span>
                                <b>
                                    <i
                                        :style="{
                                            width: `${item.percentage}%`,
                                        }"
                                    ></i>
                                </b>
                                <span>{{ item.count }}</span>
                            </li>
                        </ul>

                        <button type="button" class="solid-btn">
                            Write a Review
                        </button>
                    </aside>

                    <div class="review-stack">
                        <article
                            v-for="review in recentReviews"
                            :key="`${review.name}-${review.age}`"
                            class="review-card"
                        >
                            <div class="review-head">
                                <span class="avatar">{{
                                    review.initials
                                }}</span>
                                <div>
                                    <h4>{{ review.name }}</h4>
                                    <small>{{ review.age }}</small>
                                </div>
                                <p>
                                    {{ "★".repeat(review.rating)
                                    }}{{ "☆".repeat(5 - review.rating) }}
                                </p>
                            </div>
                            <p class="review-location">
                                Reviewed {{ review.location_name }}
                            </p>
                            <p>{{ review.text }}</p>
                        </article>

                        <p
                            v-if="recentReviews.length === 0"
                            class="review-empty"
                        >
                            No written reviews yet.
                        </p>
                    </div>
                </div>

                <p v-else class="section-empty">
                    Visitor reviews will appear here once app users rate
                    locations.
                </p>
            </section>

            <section class="section-shell events-section" id="events">
                <div class="section-head-row">
                    <div>
                        <p class="section-pill section-pill-blue">
                            Upcoming Events
                        </p>
                        <h2>What's Happening in Baao</h2>
                        <p class="section-subtitle">
                            Join local festivals, cultural events, and community
                            gatherings
                        </p>
                    </div>
                    <button type="button" class="ghost-pill">
                        View All Events
                    </button>
                </div>

                <div v-if="events.length" class="event-grid">
                    <article
                        v-for="event in events"
                        :key="event.id"
                        class="event-card"
                        :class="event.tone_class"
                    >
                        <p class="event-type">{{ event.type_label }}</p>
                        <h3>{{ event.title }}</h3>
                        <p>{{ event.description }}</p>
                        <ul>
                            <li v-if="event.date">{{ event.date }}</li>
                            <li v-if="event.time">{{ event.time }}</li>
                            <li v-if="event.venue">{{ event.venue }}</li>
                        </ul>
                        <button type="button">Learn More →</button>
                    </article>
                </div>

                <p v-else class="section-empty">
                    Upcoming events will appear here once they are published in
                    the admin panel.
                </p>

                <div class="newsletter">
                    <h3>Never Miss an Event</h3>
                    <p>
                        Subscribe to our newsletter and get updates on upcoming
                        events, festivals, and special activities in Baao.
                    </p>
                    <form @submit.prevent>
                        <input type="email" placeholder="Enter your email" />
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </section>
        </main>

        <footer class="site-footer" id="about">
            <div class="footer-shell">
                <section>
                    <a class="brand" href="/">
                        <img
                            class="brand-icon"
                            :src="appLogo"
                            alt="i-Baao logo"
                        />
                        <span class="brand-copy">
                            <strong>Explore Baao</strong>
                            <small>Tourism Mapping System</small>
                        </span>
                    </a>
                    <p>
                        Official tourism portal of Baao, Camarines Sur. Discover
                        the beauty, culture, and warmth of our town.
                    </p>
                    <div class="social-row">
                        <a href="#" aria-label="Facebook">f</a>
                        <a href="#" aria-label="Instagram">◎</a>
                        <a href="#" aria-label="X">x</a>
                        <a href="#" aria-label="YouTube">▶</a>
                    </div>
                </section>

                <section>
                    <h4>Municipality</h4>
                    <ul>
                        <li>Municipal Hall</li>
                        <li>Poblacion, Baao, Camarines Sur</li>
                        <li>tourism@baao.gov.ph</li>
                        <li>+63 (54) 123 4567</li>
                    </ul>
                </section>

                <section>
                    <h4>Navigation</h4>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="#destinations">Destinations</a></li>
                        <li><a href="#events">Events</a></li>
                        <li><a href="#about">About</a></li>
                    </ul>
                </section>

                <section>
                    <h4>Quick Links</h4>
                    <ul>
                        <li>Tourism Guide</li>
                        <li>Accommodations</li>
                        <li>Restaurants</li>
                        <li>Transportation</li>
                        <li>Emergency Contacts</li>
                    </ul>
                </section>
            </div>

            <div class="footer-bottom">
                <p>© 2026 Baao Tourism. All rights reserved.</p>
                <p>Made with care for Baao</p>
            </div>
        </footer>

        <Teleport to="body">
            <Transition name="download-modal">
                <div
                    v-if="showDownloadModal"
                    class="download-modal-backdrop"
                    @click.self="closeDownloadModal"
                >
                    <div
                        class="download-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="download-modal-title"
                    >
                        <button
                            type="button"
                            class="download-modal-close"
                            aria-label="Close"
                            @click="closeDownloadModal"
                        >
                            ×
                        </button>

                        <img
                            class="download-modal-logo"
                            :src="appLogo"
                            alt="i-Baao app logo"
                        />

                        <p class="download-modal-eyebrow">Available on mobile</p>
                        <h2 id="download-modal-title">
                            Download the i-Baao app to continue
                        </h2>
                        <p class="download-modal-copy">
                            Get directions, save favorites, and explore every
                            destination in Baao with the official i-Baao tourism
                            app.
                        </p>

                        <a
                            v-if="appDownloadUrl"
                            :href="appDownloadUrl"
                            class="download-modal-btn"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Download the App
                        </a>
                        <p v-else class="download-modal-note">
                            The download link will be available soon. Please check
                            back later.
                        </p>

                        <button
                            type="button"
                            class="download-modal-dismiss"
                            @click="closeDownloadModal"
                        >
                            Maybe later
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap");

.tourism-page {
    --bg-base: #ffffff;
    --bg-surface: #ffffff;
    --bg-soft: #ffffff;
    --bg-glass: rgba(255, 255, 255, 0.85);
    --border-glass: #dbe7e3;
    --text-main: #0c2926;
    --text-muted: #5e7873;
    --accent-primary: #0f766e;
    --accent-strong: #0b5e57;
    --accent-soft: #14b8a6;
    --accent-glow: rgba(15, 118, 110, 0.18);
    --accent-sec: #0284c7;
    --accent-warm: #f97316;
    --accent-gold: #f59e0b;
    --gradient-hero: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
    --shadow-sm: 0 4px 14px rgba(12, 41, 38, 0.06);
    --shadow-md: 0 14px 40px rgba(12, 41, 38, 0.1);
    --shadow-lg: 0 26px 60px rgba(12, 41, 38, 0.16);
    --radius-lg: 22px;
    --radius-md: 16px;

    background-color: #ffffff;
    color: var(--text-main);
    font-family: "Plus Jakarta Sans", sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes scrollBob {
    0%,
    100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    50% {
        transform: translateY(8px);
        opacity: 1;
    }
}

h1,
h2,
h3,
h4 {
    font-family: "Outfit", sans-serif;
}

/* ============ TOP NAV ============ */
.top-nav-shell {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 1000;
    background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-soft) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 4px 20px rgba(12, 41, 38, 0.12);
    transition: all 0.35s ease;
}
.top-nav-shell.nav-scrolled {
    background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-soft) 100%);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 6px 26px rgba(12, 41, 38, 0.18);
}
.top-nav-shell.mobile-menu-open {
    background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-soft) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow: 0 6px 26px rgba(12, 41, 38, 0.18);
}
.top-nav-shell.mobile-menu-open .brand {
    color: #ffffff;
}
.top-nav-shell.mobile-menu-open .hamburger-btn span {
    background: #ffffff;
}

/* Green gradient top bar: white text */
.top-nav-shell .nav-link {
    color: rgba(255, 255, 255, 0.85);
}
.top-nav-shell .nav-link:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}
.top-nav-shell .brand {
    color: #ffffff;
}
.top-nav-shell .brand-copy small {
    color: rgba(255, 255, 255, 0.75);
}
.top-nav-shell .ghost-btn {
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.4);
}
.top-nav-shell .ghost-btn:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: #ffffff;
    color: #ffffff;
}
.top-nav-shell .downloads-trigger {
    color: var(--accent-primary);
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.top-nav-shell .downloads-chevron {
    border-top-color: currentColor;
}
.top-nav-shell .hamburger-btn span {
    background: #ffffff;
}

.top-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.7rem 1.25rem;
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: var(--text-main);
}
.brand-icon {
    display: block;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    object-fit: cover;
    object-position: center top;
    flex-shrink: 0;
    border: 2px solid rgba(255, 255, 255, 0.55);
    box-shadow: 0 4px 12px rgba(12, 41, 38, 0.18);
}
.brand-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}
.brand-copy strong {
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}
.brand-copy small {
    font-size: 0.72rem;
    color: var(--text-muted);
    font-weight: 600;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.nav-link {
    position: relative;
    padding: 0.45rem 0.85rem;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    color: inherit;
    border-radius: 999px;
    transition: color 0.2s ease, background 0.2s ease;
}
.nav-scrolled .nav-link:hover {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.15);
}

.auth-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ghost-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1.1rem;
    border-radius: 999px;
    border: 1px solid var(--border-glass);
    background: transparent;
    color: var(--text-main);
    font-weight: 700;
    font-size: 0.88rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.ghost-btn:hover {
    background: var(--accent-glow);
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}

.downloads-dropdown {
    position: relative;
}
.downloads-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.5rem 1.1rem;
    border-radius: 999px;
    border: none;
    background: #ffffff;
    color: var(--accent-primary);
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(12, 41, 38, 0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.downloads-trigger:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(12, 41, 38, 0.15);
}
.downloads-chevron {
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid currentColor;
    transition: transform 0.2s ease;
}
.downloads-chevron.open {
    transform: rotate(180deg);
}
.downloads-menu {
    position: absolute;
    top: calc(100% + 0.6rem);
    right: 0;
    min-width: 16rem;
    background: #ffffff;
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-md);
    padding: 0.5rem;
    z-index: 50;
}
.downloads-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.7rem;
    border-radius: 12px;
    text-decoration: none;
    color: var(--text-main);
    transition: background 0.2s ease;
}
.downloads-item:hover {
    background: var(--bg-soft);
}
.downloads-item-icon {
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 10px;
    object-fit: cover;
}
.downloads-item-copy {
    display: flex;
    flex-direction: column;
}
.downloads-item-copy strong {
    font-size: 0.9rem;
    font-weight: 700;
}
.downloads-item-copy small {
    font-size: 0.76rem;
    color: var(--text-muted);
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

.hamburger-btn {
    display: none;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    width: 2.6rem;
    height: 2.6rem;
    border: none;
    background: transparent;
    cursor: pointer;
    padding: 0;
}
.hamburger-btn span {
    display: block;
    width: 24px;
    height: 2px;
    border-radius: 2px;
    background: var(--text-main);
    transition: all 0.3s ease;
}
.hamburger-btn span.open:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}
.hamburger-btn span.open:nth-child(2) {
    opacity: 0;
}
.hamburger-btn span.open:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

/* ============ HERO ============ */
.hero-section {
    position: relative;
    min-height: auto;
    background-color: #ffffff;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100' viewBox='0 0 56 100'%3E%3Cpath d='M28 66L0 50L0 16L28 0L56 16L56 50L28 66zm0-2l26-15V17L28 3L2 17v32l26 15zM28 98L0 82L0 66L28 50L56 66L56 82L28 98zm0-2l26-15V67L28 53L2 67v13l26 15z' fill='%230f766e' fill-opacity='0.035' fill-rule='evenodd'/%3E%3C/svg%3E");
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 7rem 0 0;
    overflow: hidden;
    border-bottom: 1px solid var(--border-glass);
}
.hero-inner {
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: flex-end;
    gap: 0;
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
    padding: 0 2.5rem;
}
.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    padding-bottom: 4rem;
    animation: fadeUp 0.7s ease both;
}
.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 1rem;
    border-radius: 999px;
    background: var(--bg-soft);
    border: 1px solid var(--border-glass);
    color: var(--accent-primary);
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.hero-pill-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--accent-soft);
}
.hero-content h1 {
    margin: 1rem 0 0;
    color: var(--text-main);
    font-size: clamp(3rem, 5.5vw, 4.8rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.03em;
}
.hero-content h1 span {
    display: block;
    background: linear-gradient(120deg, var(--accent-primary) 0%, var(--accent-soft) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
.hero-subtitle {
    margin: 1rem 0 0;
    max-width: 28rem;
    color: var(--text-muted);
    font-size: clamp(0.97rem, 1.2vw, 1.08rem);
    line-height: 1.65;
}

.search-shell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    width: 100%;
    max-width: 30rem;
    background: #ffffff;
    border: 1px solid var(--border-glass);
    border-radius: 999px;
    padding: 0.45rem 0.45rem 0.45rem 1.15rem;
    box-shadow: var(--shadow-sm);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.search-shell:focus-within {
    border-color: var(--accent-primary);
    box-shadow: none;
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
    background: linear-gradient(
        135deg,
        var(--accent-primary),
        var(--accent-soft)
    );
    color: #ffffff;
    font-weight: 700;
    font-size: 0.92rem;
    transition: transform 0.2s ease;
}
.search-shell button:hover {
    transform: translateY(-1px);
}
.search-result {
    margin-top: 0.85rem;
    color: var(--text-muted);
    font-size: 0.88rem;
    font-weight: 600;
}

.hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 2.5rem;
    margin: 2.5rem 0 0;
    padding: 0;
}
.hero-stat dt {
    font-family: "Outfit", sans-serif;
    font-size: clamp(1.6rem, 2.5vw, 2.1rem);
    font-weight: 800;
    color: var(--accent-primary);
    line-height: 1;
}
.hero-stat dd {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--text-muted);
}

/* Hero visual (right column) */
.hero-visual {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    animation: fadeUp 0.85s 0.15s ease both;
}
.hero-traveler-img {
    width: 100%;
    max-width: 100%;
    height: clamp(620px, 72vh, 780px);
    display: block;
    object-fit: contain;
    object-position: bottom center;
    filter: contrast(1.07) brightness(1.02) saturate(1.15);
}

/* ============ MOBILE NAV ============ */
.mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.75rem 1.25rem 1.25rem;
    background: #ffffff;
    border-top: 1px solid var(--border-glass);
}
.mobile-nav-link {
    padding: 0.85rem 0.5rem;
    font-weight: 700;
    color: var(--text-main);
    text-decoration: none;
    border-bottom: 1px solid var(--bg-soft);
}
.mobile-section-label {
    margin: 1rem 0 0.5rem;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--text-muted);
}
.mobile-download-card {
    margin-top: 0.5rem;
}
.mobile-download-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem;
    border-radius: var(--radius-md);
    background: var(--bg-soft);
    text-decoration: none;
    color: var(--text-main);
}
.mobile-download-icon {
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 12px;
    object-fit: cover;
}
.mobile-download-copy {
    display: flex;
    flex-direction: column;
    flex: 1;
}
.mobile-download-copy strong {
    font-size: 0.92rem;
    font-weight: 800;
}
.mobile-download-copy small {
    font-size: 0.76rem;
    color: var(--text-muted);
}
.mobile-download-arrow {
    font-size: 1.2rem;
    color: var(--accent-primary);
    font-weight: 800;
}
.mobile-auth {
    margin-top: 1rem;
}
.mobile-login-btn {
    display: block;
    text-align: center;
    padding: 0.85rem;
    border-radius: 999px;
    background: linear-gradient(
        135deg,
        var(--accent-primary),
        var(--accent-soft)
    );
    color: #ffffff;
    font-weight: 800;
    text-decoration: none;
}

.mobile-menu-enter-active {
    transition: all 0.3s ease;
}
.mobile-menu-leave-active {
    transition: all 0.25s ease;
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* ============ SECTIONS / SHARED ============ */
.main-content {
    --section-x: 1.25rem;
    position: relative;
    z-index: 3;
    background: #ffffff;
}
.section-shell {
    max-width: 1200px;
    margin: 0 auto;
    padding: 4.5rem var(--section-x);
}
.section-shell > h2 {
    margin: 0.65rem 0 0;
    font-size: clamp(1.8rem, 3.4vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.02em;
}
.section-subtitle {
    margin: 0.6rem 0 0;
    color: var(--text-muted);
    font-size: 1rem;
    line-height: 1.5;
    max-width: 40rem;
}
.section-head-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.section-empty {
    margin-top: 2rem;
    padding: 2.5rem;
    text-align: center;
    color: var(--text-muted);
    background: var(--bg-surface);
    border: 1px dashed var(--border-glass);
    border-radius: var(--radius-lg);
    font-weight: 600;
}

.section-pill {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    background: var(--accent-glow);
    color: var(--accent-primary);
}
.section-pill-peach {
    background: rgba(249, 115, 22, 0.12);
    color: #ea580c;
}
.section-pill-mint {
    background: rgba(16, 185, 129, 0.14);
    color: #059669;
}
.section-pill-blue {
    background: rgba(2, 132, 199, 0.12);
    color: #0284c7;
}
.section-pill-gold {
    background: rgba(245, 158, 11, 0.16);
    color: #d97706;
}

.solid-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.8rem 1.4rem;
    border: none;
    border-radius: 999px;
    background: linear-gradient(
        135deg,
        var(--accent-primary),
        var(--accent-soft)
    );
    color: #ffffff;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 10px 24px var(--accent-glow);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.solid-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.ghost-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.7rem 1.3rem;
    border-radius: 999px;
    border: 1px solid var(--border-glass);
    background: var(--bg-surface);
    color: var(--text-main);
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}
.ghost-pill:hover {
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}
.outline-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

/* ============ WHY VISIT ============ */
.why-visit-section {
    background: #ffffff;
    padding: 0;
}
.why-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    margin-top: 3rem;
    border-top: 1px solid var(--border-glass);
}
.why-item {
    display: flex;
    align-items: flex-start;
    gap: 1.1rem;
    padding: 1.75rem 1.5rem 1.75rem 0;
    border-bottom: 1px solid var(--border-glass);
}
.why-item:nth-child(odd) {
    border-right: 1px solid var(--border-glass);
    padding-right: 2rem;
}
.why-item:nth-child(even) {
    padding-left: 2rem;
}
.why-icon-wrap {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--bg-soft);
    border: 1px solid var(--border-glass);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-primary);
    margin-top: 2px;
}
.why-text h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.35rem;
    line-height: 1.3;
}
.why-text p {
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.65;
}

/* ============ EXPLORE BY CATEGORY ============ */
.category-explore-section {
    background: #ffffff;
}
.cat-explore-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}
.cat-explore-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.5rem 1rem;
    border: 1.5px solid var(--border-glass);
    border-radius: var(--radius-md);
    background: #ffffff;
    cursor: pointer;
    transition: all 0.22s ease;
    text-align: center;
}
.cat-explore-card:hover {
    border-color: var(--accent-primary);
    background: var(--bg-soft);
    transform: translateY(-3px);
    box-shadow: var(--shadow-sm);
}
.cat-explore-icon {
    font-size: 2rem;
    line-height: 1;
}
.cat-explore-label {
    font-size: 0.88rem;
    font-weight: 800;
    color: var(--text-main);
}
.cat-explore-count {
    font-size: 0.74rem;
    font-weight: 600;
    color: var(--accent-primary);
    background: var(--accent-glow);
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
}

/* ============ TRAVEL TIPS ============ */
.travel-tips-section {
    background: #ffffff;
}
.tips-list {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 0 3rem;
    margin-top: 3rem;
}
.tips-col {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.tips-divider {
    width: 1px;
    background: var(--border-glass);
    align-self: stretch;
}
.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.4rem 0;
    border-bottom: 1px solid var(--border-glass);
}
.tip-item:first-child {
    padding-top: 0;
}
.tip-item:last-child {
    border-bottom: none;
}
.tip-icon-wrap {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--bg-soft);
    border: 1px solid var(--border-glass);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-primary);
    margin-top: 2px;
}
.tip-item h4 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text-main);
    margin-bottom: 0.3rem;
    line-height: 1.3;
}
.tip-item p {
    font-size: 0.88rem;
    color: var(--text-muted);
    line-height: 1.6;
}

/* ============ MOBILE APP ============ */
.app-section {
    background: linear-gradient(135deg, #042f2e 0%, #0f766e 100%);
    color: #ffffff;
    padding: 5rem 1.25rem;
    width: 100%;
    box-shadow: inset 0 20px 40px rgba(0,0,0,0.08), inset 0 -20px 40px rgba(0,0,0,0.08);
}
.mobile-app-feature {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    display: grid;
    grid-template-columns: 1.1fr 1fr auto;
    gap: 2rem;
    align-items: center;
}
.mobile-app-intro h3 {
    margin: 0.85rem 0 0.75rem;
    font-size: 1.8rem;
    font-weight: 800;
}
.mobile-app-intro p {
    color: rgba(255, 255, 255, 0.82);
    line-height: 1.6;
}
.mobile-app-intro .section-pill-blue {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
}
.mobile-app-download-btn {
    margin-top: 1.5rem;
    background: #ffffff;
    color: var(--accent-primary);
}
.mobile-app-download-btn:hover {
    background: #fbbf24;
    color: #042f2e;
}
.mobile-app-feature-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}
.mobile-app-feature-list li {
    display: flex;
    gap: 0.9rem;
    align-items: flex-start;
}
.mobile-app-feature-icon {
    flex-shrink: 0;
    width: 2.6rem;
    height: 2.6rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.14);
    font-size: 1.2rem;
}
.mobile-app-feature-list strong {
    font-size: 0.98rem;
    font-weight: 800;
}
.mobile-app-feature-list p {
    margin: 0.2rem 0 0;
    font-size: 0.84rem;
    color: rgba(255, 255, 255, 0.74);
    line-height: 1.45;
}
.mobile-app-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 2rem 2.5rem;
    border-radius: var(--radius-md);
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.mobile-app-preview-logo {
    width: 5rem;
    height: 5rem;
    border-radius: 22px;
    object-fit: cover;
    margin-bottom: 0.5rem;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.3);
}
.mobile-app-preview strong {
    font-size: 1.2rem;
    font-weight: 800;
}
.mobile-app-preview small {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.74);
}
.mobile-app-version {
    margin-top: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    font-size: 0.74rem;
    font-weight: 800;
}

/* ============ DESTINATION SWIPE ============ */
.destination-swipe-shell {
    margin-top: 2rem;
}
.destination-swipe-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.destination-swipe-count {
    font-weight: 800;
    color: var(--accent-primary);
}
.destination-swipe-hint {
    font-size: 0.82rem;
    color: var(--text-muted);
    font-weight: 600;
}
.destination-swipe-hint-mobile {
    display: none;
}
.destination-swipe-stage {
    position: relative;
}
.destination-swipe-viewport {
    overflow: hidden;
    touch-action: pan-y;
    cursor: grab;
    user-select: none;
}
.destination-swipe-viewport:active {
    cursor: grabbing;
}
.destination-swipe-track {
    display: flex;
    width: 100%;
    transition: transform 0.38s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform;
}
.destination-swipe-track.dragging {
    transition: none;
}
.destination-slide {
    flex: 0 0 100%;
    width: 100%;
    min-width: 100%;
}
.destination-swipe-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 6;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    border: 1px solid var(--border-glass);
    background: #ffffff;
    color: var(--text-main);
    font-size: 1.2rem;
    cursor: pointer;
    box-shadow: var(--shadow-md);
    transition: all 0.2s ease;
}
.destination-swipe-arrow:hover {
    background: var(--accent-primary);
    color: #ffffff;
    border-color: transparent;
}
.destination-swipe-arrow-prev {
    left: 0;
}
.destination-swipe-arrow-next {
    right: 0;
}
.destination-swipe-dots {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
}
.destination-swipe-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--border-glass);
    transition: all 0.2s ease;
}
.destination-swipe-dot.active {
    width: 26px;
    border-radius: 999px;
    background: var(--accent-primary);
}

.destination-layout {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 1.5rem;
}
.destination-gallery {
    display: grid;
    grid-template-columns: 2fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 0.75rem;
    height: 100%;
    min-height: 420px;
}
.main-photo {
    grid-row: 1 / 3;
    border-radius: var(--radius-md);
    background-size: cover;
    background-position: center;
    background-color: var(--bg-soft);
    min-height: 220px;
}
.side-photo {
    border-radius: var(--radius-md);
    background-size: cover;
    background-position: center;
    background-color: var(--bg-soft);
    min-height: 100px;
}
.gallery-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-weight: 600;
    background: var(--bg-soft);
}
.destination-gallery.gallery-single {
    grid-template-columns: 1fr;
    grid-template-rows: 1fr;
}
.destination-gallery.gallery-single .main-photo {
    grid-row: auto;
}
.destination-gallery.gallery-duo {
    grid-template-rows: 1fr;
}
.destination-gallery.gallery-duo .main-photo {
    grid-row: auto;
}
.gallery-extra {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
    gap: 0.5rem;
}
.gallery-thumb {
    height: 64px;
    border-radius: 12px;
    background-size: cover;
    background-position: center;
}

.destination-side {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.spot-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.spot-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.spot-card-head h3 {
    font-size: 1.35rem;
    font-weight: 800;
}
.spot-card-head p {
    margin: 0.25rem 0 0;
    color: var(--accent-primary);
    font-weight: 700;
    font-size: 0.85rem;
}
.spot-card-icons {
    display: flex;
    gap: 0.5rem;
}
.spot-card-icons button {
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 50%;
    border: 1px solid var(--border-glass);
    background: var(--bg-surface);
    cursor: pointer;
    font-size: 1rem;
    color: var(--text-main);
    transition: all 0.2s ease;
}
.spot-card-icons button:hover {
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}

/* ============ DOWNLOAD MODAL ============ */
.download-modal-backdrop {
    --text-main: #0c2926;
    --text-muted: #5e7873;
    --accent-primary: #0f766e;
    --accent-soft: #14b8a6;
    --accent-glow: rgba(15, 118, 110, 0.18);
    --bg-soft: #f4f7f6;
    --shadow-lg: 0 26px 60px rgba(12, 41, 38, 0.16);

    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: rgba(12, 41, 38, 0.45);
}

.download-modal {
    position: relative;
    width: 100%;
    max-width: 420px;
    padding: 2rem 1.75rem 1.5rem;
    border-radius: 1.25rem;
    background: #ffffff;
    text-align: center;
    box-shadow: var(--shadow-lg);
}

.download-modal-close {
    position: absolute;
    top: 0.85rem;
    right: 0.85rem;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 999px;
    background: var(--bg-soft);
    color: var(--text-muted);
    font-size: 1.35rem;
    line-height: 1;
    cursor: pointer;
}

.download-modal-logo {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    display: block;
}

.download-modal-eyebrow {
    margin: 0 0 0.35rem;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--accent-primary);
}

.download-modal h2 {
    margin: 0;
    font-family: "Outfit", sans-serif;
    font-size: 1.45rem;
    line-height: 1.25;
    color: var(--text-main);
}

.download-modal-copy {
    margin: 0.85rem 0 1.35rem;
    color: var(--text-muted);
    line-height: 1.65;
    font-size: 0.95rem;
}

.download-modal-btn {
    display: inline-flex;
    width: 100%;
    justify-content: center;
    text-decoration: none;
    padding: 0.85rem 1.4rem;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    color: #ffffff !important;
    font-weight: 700;
    font-size: 0.95rem;
    box-shadow: 0 10px 24px rgba(15, 118, 110, 0.18);
}

.download-modal-note {
    margin: 0 0 1rem;
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.5;
}

.download-modal-dismiss {
    margin-top: 0.85rem;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
}

.download-modal-enter-active,
.download-modal-leave-active {
    transition: opacity 0.2s ease;
}

.download-modal-enter-active .download-modal,
.download-modal-leave-active .download-modal {
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.download-modal-enter-from,
.download-modal-leave-to {
    opacity: 0;
}

.download-modal-enter-from .download-modal,
.download-modal-leave-to .download-modal {
    opacity: 0;
    transform: translateY(12px) scale(0.98);
}

.spot-rating {
    margin: 1rem 0 0;
    font-size: 0.9rem;
    color: var(--text-muted);
}
.spot-rating span {
    color: var(--accent-gold);
    font-weight: 800;
}
.spot-rating-empty {
    color: var(--text-muted);
}
.spot-description {
    margin: 1rem 0 0;
    color: var(--text-muted);
    line-height: 1.65;
    font-size: 0.95rem;
}
.spot-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.info-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.info-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.info-tabs button {
    padding: 0.5rem 1rem;
    border-radius: 999px;
    border: none;
    background: var(--bg-soft);
    color: var(--text-muted);
    font-weight: 700;
    font-size: 0.82rem;
    cursor: pointer;
}
.info-tabs button.active {
    background: var(--accent-glow);
    color: var(--accent-primary);
}
.info-card ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.info-card li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--bg-soft);
    font-size: 0.88rem;
}
.info-card li:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.info-card li strong {
    color: var(--text-muted);
    font-weight: 700;
}
.info-card li span {
    color: var(--text-main);
    font-weight: 700;
    text-align: right;
}

/* ============ REVIEWS ============ */
.reviews-section {
    background: #ffffff;
}
.reviews-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.5rem;
    margin-top: 2rem;
    align-items: start;
}
.rating-panel {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: 1.75rem;
    text-align: center;
    position: sticky;
    top: 90px;
}
.rating-panel h3 {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1;
    color: var(--text-main);
}
.rating-panel .stars {
    color: var(--accent-gold);
    font-size: 1.2rem;
    letter-spacing: 0.1em;
    margin: 0.4rem 0;
}
.rating-panel > small {
    color: var(--text-muted);
    font-size: 0.82rem;
}
.rating-panel ul {
    list-style: none;
    margin: 1.5rem 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}
.rating-panel li {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.82rem;
    color: var(--text-muted);
    font-weight: 700;
}
.rating-panel li b {
    flex: 1;
    height: 8px;
    border-radius: 999px;
    background: var(--bg-soft);
    overflow: hidden;
}
.rating-panel li b i {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--accent-gold), #fbbf24);
}
.review-stack {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.25rem;
}
.review-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.review-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}
.review-head {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.avatar {
    width: 2.8rem;
    height: 2.8rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        var(--accent-primary),
        var(--accent-soft)
    );
    color: #ffffff;
    font-weight: 800;
    font-size: 0.95rem;
}
.review-head h4 {
    font-size: 0.98rem;
    font-weight: 800;
}
.review-head small {
    color: var(--text-muted);
    font-size: 0.78rem;
}
.review-head > p {
    margin-left: auto;
    color: var(--accent-gold);
    font-size: 0.92rem;
}
.review-location {
    margin: 1rem 0 0.5rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--accent-primary);
}
.review-card > p:last-child {
    color: var(--text-muted);
    line-height: 1.6;
    font-size: 0.92rem;
}
.review-empty {
    color: var(--text-muted);
    font-weight: 600;
}

/* ============ EVENTS ============ */
.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}
.event-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    padding: 1.6rem;
    position: relative;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.event-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    background: linear-gradient(
        180deg,
        var(--accent-primary),
        var(--accent-soft)
    );
}
.event-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}
.event-type {
    display: inline-block;
    padding: 0.3rem 0.75rem;
    border-radius: 999px;
    background: var(--accent-glow);
    color: var(--accent-primary);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.event-card h3 {
    margin: 0.9rem 0 0.6rem;
    font-size: 1.3rem;
    font-weight: 800;
}
.event-card > p {
    color: var(--text-muted);
    line-height: 1.55;
    font-size: 0.92rem;
}
.event-card ul {
    list-style: none;
    margin: 1.1rem 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.event-card ul li {
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--text-main);
    padding-left: 1.2rem;
    position: relative;
}
.event-card ul li::before {
    content: "•";
    position: absolute;
    left: 0;
    color: var(--accent-primary);
    font-weight: 800;
}
.event-card > button {
    border: none;
    background: transparent;
    color: var(--accent-primary);
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    padding: 0;
}

.newsletter {
    margin-top: 2.5rem;
    background: linear-gradient(135deg, #042f2e 0%, #0f766e 100%);
    border-radius: var(--radius-lg);
    padding: 2.75rem;
    text-align: center;
    color: #ffffff;
    box-shadow: var(--shadow-lg);
}
.newsletter h3 {
    font-size: 1.7rem;
    font-weight: 800;
}
.newsletter p {
    margin: 0.7rem auto 1.5rem;
    max-width: 32rem;
    color: rgba(255, 255, 255, 0.82);
    line-height: 1.55;
}
.newsletter form {
    display: flex;
    gap: 0.5rem;
    max-width: 26rem;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 999px;
    padding: 0.4rem;
}
.newsletter input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 0.6rem 1rem;
    color: #ffffff;
    font-size: 0.92rem;
    min-width: 0;
}
.newsletter input::placeholder {
    color: rgba(255, 255, 255, 0.65);
}
.newsletter button {
    border: none;
    cursor: pointer;
    padding: 0.65rem 1.4rem;
    border-radius: 999px;
    background: #ffffff;
    color: var(--accent-primary);
    font-weight: 800;
    font-size: 0.88rem;
}

/* ============ FOOTER ============ */
.site-footer {
    background: #042f2e;
    color: rgba(255, 255, 255, 0.78);
    padding: 3.5rem 1.25rem 1.5rem;
}
.footer-shell {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1fr;
    gap: 2rem;
}
.site-footer .brand {
    color: #ffffff;
}
.site-footer .brand-copy small {
    color: rgba(255, 255, 255, 0.6);
}
.site-footer section > p {
    margin: 1rem 0;
    line-height: 1.6;
    font-size: 0.9rem;
    max-width: 22rem;
}
.social-row {
    display: flex;
    gap: 0.6rem;
}
.social-row a {
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    text-decoration: none;
    font-weight: 700;
    transition: background 0.2s ease;
}
.social-row a:hover {
    background: var(--accent-soft);
}
.site-footer h4 {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 1rem;
}
.site-footer ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}
.site-footer ul li,
.site-footer ul li a {
    font-size: 0.88rem;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: color 0.2s ease;
}
.site-footer ul li a:hover {
    color: #ffffff;
}
.footer-bottom {
    max-width: 1200px;
    margin: 2.5rem auto 0;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.55);
}

/* ============ RESPONSIVE ============ */
@media (min-width: 769px) {
    .destination-swipe-viewport {
        padding: 0 4rem;
        cursor: default;
        user-select: auto;
    }
    .destination-swipe-viewport:active {
        cursor: default;
    }
}

@media (max-width: 1024px) {
    .mobile-app-feature {
        grid-template-columns: 1fr 1fr;
    }
    .mobile-app-preview {
        grid-column: 1 / -1;
        flex-direction: row;
        justify-content: center;
        gap: 1rem;
    }
    .mobile-app-preview-logo {
        margin-bottom: 0;
    }
}

@media (max-width: 900px) {
    .reviews-layout {
        grid-template-columns: 1fr;
    }
    .rating-panel {
        position: static;
    }
}

@media (max-width: 768px) {
    .nav-links,
    .auth-actions {
        display: none;
    }
    .hamburger-btn {
        display: flex;
    }

    .mobile-menu-enter-active {
        transition: all 0.3s ease;
    }
    .mobile-menu-leave-active {
        transition: all 0.25s ease;
    }
    .mobile-menu-enter-from,
    .mobile-menu-leave-to {
        opacity: 0;
        transform: translateY(-10px);
    }

    .hero-section {
        min-height: auto;
        padding: 6.5rem 0 0;
    }
    .hero-inner {
        grid-template-columns: 1fr;
        padding: 0 1.25rem;
        gap: 0;
    }
    .hero-visual {
        order: 1;
        justify-content: center;
        margin-left: 0;
    }
    .hero-content {
        order: 0;
    }
    .hero-traveler-img {
        height: clamp(360px, 65vw, 460px);
    }
    .hero-content {
        align-items: center;
        text-align: center;
        padding-right: 0;
        padding-bottom: 3rem;
    }
    .hero-subtitle {
        max-width: 100%;
    }
    .search-shell {
        max-width: 100%;
    }
    .hero-stats {
        justify-content: center;
        gap: 1.5rem;
    }
    .main-content {
        --section-x: 1rem;
    }
    .section-shell {
        padding: 3rem var(--section-x);
    }
    .app-section {
        padding: 3.5rem var(--section-x);
    }
    .mobile-app-feature {
        grid-template-columns: 1fr;
        padding: 0;
    }
    .mobile-app-preview {
        flex-direction: column;
    }

    .destination-layout {
        grid-template-columns: 1fr;
    }
    .destination-gallery {
        min-height: 280px;
    }
    .destination-swipe-hint-mobile {
        display: block;
    }
    .destination-swipe-arrow {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 1rem;
    }
    .destination-slide {
        padding-inline: 0.25rem;
        box-sizing: border-box;
    }

    .footer-shell {
        grid-template-columns: 1fr 1fr;
    }
    .footer-bottom {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}

@media (max-width: 540px) {
    .hero-content h1 {
        font-size: clamp(2.2rem, 9vw, 3rem);
    }
    .search-shell {
        flex-wrap: wrap;
        border-radius: 20px;
        padding: 0.75rem;
    }
    .search-shell button {
        width: 100%;
    }
    .hero-stats {
        gap: 1.1rem 1.5rem;
    }
    .why-list {
        grid-template-columns: 1fr;
    }
    .why-item:nth-child(odd) {
        border-right: none;
        padding-right: 0;
    }
    .why-item:nth-child(even) {
        padding-left: 0;
    }
    .tips-list {
        grid-template-columns: 1fr;
    }
    .tips-divider {
        display: none;
    }
    .footer-shell {
        grid-template-columns: 1fr;
    }
    .newsletter form {
        flex-direction: column;
        border-radius: 18px;
    }
    .newsletter button {
        width: 100%;
    }
}
</style>
