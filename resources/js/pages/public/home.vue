<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import L from "leaflet";
import "leaflet/dist/leaflet.css";
import { Link } from "@inertiajs/vue3";

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
});

const appLogo = "/assets/images/applogo.png";
const appDownloadUrl =
    "https://github.com/baklod/bnapp/releases/download/V2/i-baao.apk";

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
    { name: "Map", href: "#map" },
    { name: "Destinations", href: "#destinations" },
    { name: "Events", href: "#events" },
    { name: "About", href: "#about" },
];

const categoryColors = {
    nature: "#2a9d8f",
    culture: "#e2a30b",
    food: "#f06a2a",
    resorts: "#2f9fc7",
    religious: "#7c3aed",
    historical: "#b45309",
    parks: "#16a34a",
    lakes_water: "#0891b2",
    landmarks: "#dc2626",
    markets: "#ca8a04",
    adventure: "#ea580c",
    museums: "#9333ea",
    accommodation: "#2563eb",
    entertainment: "#db2777",
    wellness: "#0d9488",
    shopping: "#4f46e5",
    community: "#64748b",
};

const mapElement = ref(null);
const heroSpots = ref([...props.locations]);
const isCarouselAnimating = ref(false);
const activeHeroSpot = computed(() => heroSpots.value[0] ?? null);
const searchTerm = ref("");
const activeCategory = ref("all");
const selectedDestinationId = ref(props.locations[0]?.id ?? null);
const mobileMenuOpen = ref(false);
const navScrolled = ref(false);
const downloadsOpen = ref(false);
const downloadsDropdownRef = ref(null);

let leafletMap = null;
let markerLayer = null;
let skipNextDestinationFocus = false;
const markerRegistry = new Map();

const filteredDestinations = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();

    return props.locations.filter((destination) => {
        const categoryMatch =
            activeCategory.value === "all" ||
            destination.category === activeCategory.value;

        if (!categoryMatch) {
            return false;
        }

        if (!term) {
            return true;
        }

        const haystack = [
            destination.name,
            destination.category_label,
            destination.summary,
            destination.description,
            destination.category,
        ]
            .join(" ")
            .toLowerCase();

        return haystack.includes(term);
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

const mapLegend = computed(() =>
    props.categories
        .filter((category) => category.key !== "all")
        .map((category) => ({
            label: category.label,
            color: categoryColors[category.key] ?? "#64748b",
        })),
);

const resultMessage = computed(() => {
    if (!searchTerm.value.trim()) {
        if (props.locations.length === 0) {
            return "No destinations available yet";
        }

        return `${props.locations.length} destination${
            props.locations.length === 1 ? "" : "s"
        } across Baao`;
    }

    if (filteredDestinations.value.length === 0) {
        return "No exact match. Try another keyword.";
    }

    return `${filteredDestinations.value.length} destination matches`;
});

const averageRating = computed(
    () =>
        activeDestination.value?.average_rating ??
        props.reviewSummary.average_rating ??
        null,
);

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

function markerFill(category, isActive) {
    if (isActive) {
        return "#1f7a6e";
    }

    return categoryColors[category] ?? "#2a9d8f";
}

function markerStyle(destination, isActive = false) {
    return {
        radius: isActive ? 10 : 8,
        color: "#ffffff",
        weight: 2,
        fillColor: markerFill(destination.category, isActive),
        fillOpacity: 1,
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
        skipNextDestinationFocus = true;
        selectedDestinationId.value = filteredDestinations.value[0].id;
    }
}

function selectCategory(key) {
    activeCategory.value = key;
}

function selectDestination(destinationId) {
    selectedDestinationId.value = destinationId;
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

function cycleHeroSpot(index) {
    if (isCarouselAnimating.value) return;
    isCarouselAnimating.value = true;

    const item = heroSpots.value.splice(index, 1)[0];
    heroSpots.value.push(item);

    setTimeout(() => {
        isCarouselAnimating.value = false;
    }, 500);
}

function setMarkerHighlight() {
    markerRegistry.forEach((entry, id) => {
        entry.marker.setStyle(
            markerStyle(entry.destination, id === selectedDestinationId.value),
        );
    });
}

function renderLeafletMarkers(shouldFitBounds = false) {
    if (!leafletMap || !markerLayer) {
        return;
    }

    markerLayer.clearLayers();
    markerRegistry.clear();

    if (filteredDestinations.value.length === 0) {
        return;
    }

    filteredDestinations.value.forEach((destination) => {
        const marker = L.circleMarker(
            [destination.latitude, destination.longitude],
            markerStyle(
                destination,
                destination.id === selectedDestinationId.value,
            ),
        );

        marker.bindTooltip(destination.map_label || destination.name, {
            direction: "top",
            offset: [0, -8],
            opacity: 0.95,
            autoPan: false,
        });

        marker.on("click", () => {
            selectDestination(destination.id);
        });

        marker.addTo(markerLayer);
        markerRegistry.set(destination.id, { marker, destination });
    });

    if (shouldFitBounds) {
        const bounds = L.latLngBounds(
            filteredDestinations.value.map((destination) => [
                destination.latitude,
                destination.longitude,
            ]),
        );

        if (bounds.isValid()) {
            leafletMap.fitBounds(bounds.pad(0.34), {
                animate: false,
            });
        }
    }

    setMarkerHighlight();
}

function focusActiveDestination(animate = true) {
    const active = activeDestination.value;

    if (!leafletMap || !active) {
        return;
    }

    leafletMap.flyTo(
        [active.latitude, active.longitude],
        Math.max(leafletMap.getZoom(), 14),
        {
            animate,
            duration: animate ? 0.5 : 0,
        },
    );

    setMarkerHighlight();
}

function initializeLeafletMap() {
    if (!mapElement.value || leafletMap) {
        return;
    }

    leafletMap = L.map(mapElement.value, {
        zoomControl: false,
        scrollWheelZoom: false,
        attributionControl: true,
    }).setView([13.4548, 123.3658], 13);

    leafletMap.on("mouseover", () => {
        leafletMap.scrollWheelZoom.enable();
    });
    leafletMap.on("mouseout", () => {
        leafletMap.scrollWheelZoom.disable();
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(leafletMap);

    markerLayer = L.layerGroup().addTo(leafletMap);

    renderLeafletMarkers(true);
    focusActiveDestination(false);
    leafletMap.invalidateSize();
}

watch(activeCategory, () => {
    syncSelectionAfterFilter();
    renderLeafletMarkers(true);
});

watch(searchTerm, () => {
    syncSelectionAfterFilter();
    renderLeafletMarkers(false);
});

watch(selectedDestinationId, () => {
    setMarkerHighlight();

    if (skipNextDestinationFocus) {
        skipNextDestinationFocus = false;
        return;
    }

    focusActiveDestination();
});

function handleScroll() {
    navScrolled.value = window.scrollY > 80;
}

function toggleDownloads() {
    downloadsOpen.value = !downloadsOpen.value;
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

    initializeLeafletMap();
    window.addEventListener("scroll", handleScroll, { passive: true });
    document.addEventListener("click", handleDocumentClick);
    handleScroll();
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
    markerRegistry.clear();

    if (leafletMap) {
        leafletMap.remove();
        leafletMap = null;
        markerLayer = null;
    }
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
                    <img
                        class="brand-icon"
                        :src="appLogo"
                        alt="i-Baao logo"
                    />
                    <span class="brand-copy">
                        <strong>Tourism Mapping System</strong>
                        <small>Municipality of Baao</small>
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
                            Downloads
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
                                        <small>Download APK (V2)</small>
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
                    <div class="mobile-download-card">
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
                                <small>Android APK (V2)</small>
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

        <section
            class="hero-section"
            :style="{
                backgroundImage: activeHeroSpot
                    ? 'linear-gradient(to right, rgba(15, 23, 42, 0.72) 0%, rgba(15, 23, 42, 0.4) 50%, rgba(0, 0, 0, 0.12) 100%), url(' +
                      activeHeroSpot.image_url +
                      ')'
                    : 'linear-gradient(135deg, #059669 0%, #10b981 100%)',
            }"
        >
            <div class="hero-grid"></div>

            <div class="hero-content">
                <p class="hero-pill">Explore Baao, Camarines Sur</p>
                <h1>
                    Discover the<br />
                    <span>Heart of Baao</span>
                </h1>
                <p class="hero-subtitle">
                    Your gateway to the best of Baao — from serene lakeside
                    views and heritage landmarks to local food spots and hidden
                    resorts. Start exploring now.
                </p>

                <form class="search-shell" @submit.prevent>
                    <span class="search-icon">⌕</span>
                    <input
                        v-model="searchTerm"
                        type="search"
                        placeholder="Search places, resorts, restaurants..."
                    />
                    <button type="submit">Search</button>
                </form>

                <p class="search-result">{{ resultMessage }}</p>
            </div>

            <div v-if="heroSpots.length" class="hero-spots-carousel">
                <TransitionGroup name="carousel-queue">
                    <div
                        v-for="(dest, index) in heroSpots"
                        :key="dest.id"
                        class="spot-card-mini"
                        :class="{
                            'spot-card-mini-active': index === 0,
                        }"
                        @click="cycleHeroSpot(index)"
                    >
                        <img :src="dest.image_url" :alt="dest.name" />
                        <div class="spot-card-mini-info">
                            <small
                                >{{ dest.category_label }} &bull; Camarines
                                Sur</small
                            >
                            <strong>{{ dest.name }}</strong>
                        </div>
                    </div>
                </TransitionGroup>
            </div>
        </section>

        <main class="main-content">
            <section class="section-shell map-section" id="map">
                <p class="section-pill section-pill-peach">Explore</p>
                <h2>Interactive Map</h2>
                <p class="section-subtitle">
                    Discover attractions, restaurants, and hidden gems across
                    Baao
                </p>

                <div class="category-row">
                    <button
                        v-for="category in categories"
                        :key="category.key"
                        type="button"
                        class="category-chip"
                        :class="{
                            'category-chip-active':
                                category.key === activeCategory,
                        }"
                        @click="selectCategory(category.key)"
                    >
                        {{ category.label }}
                    </button>
                </div>

                <div class="map-layout">
                    <aside class="spots-sidebar">
                        <div class="sidebar-head">
                            <h3>Available Spots</h3>
                            <span
                                >{{
                                    filteredDestinations.length
                                }}
                                locations</span
                            >
                        </div>
                        <ul class="spot-list">
                            <li
                                v-if="filteredDestinations.length === 0"
                                class="spot-empty"
                            >
                                No locations match your search.
                            </li>
                            <li
                                v-for="dest in filteredDestinations"
                                :key="dest.id"
                                :class="{
                                    'spot-active':
                                        selectedDestinationId === dest.id,
                                }"
                                @click="selectDestination(dest.id)"
                            >
                                <div class="spot-info">
                                    <strong>{{ dest.name }}</strong>
                                    <small
                                        >{{ dest.map_label }} &bull;
                                        {{ dest.category_label }}</small
                                    >
                                </div>
                                <span class="spot-arrow">→</span>
                            </li>
                        </ul>
                    </aside>

                    <div class="map-card outline-card">
                        <div class="map-card-head">
                            <h3>Interactive Map</h3>
                            <ul class="map-legend">
                                <li
                                    v-for="legend in mapLegend"
                                    :key="legend.label"
                                >
                                    <span
                                        :style="{ background: legend.color }"
                                    ></span>
                                    {{ legend.label }}
                                </li>
                            </ul>
                        </div>
                        <div class="map-canvas-wrap">
                            <div ref="mapElement" class="leaflet-map"></div>
                        </div>
                    </div>
                </div>

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

            <section
                class="section-shell destination-section"
                id="destinations"
            >
                <p class="section-pill section-pill-mint">
                    Destination Details
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
                                        :class="galleryLayoutClass(destination)"
                                    >
                                        <div
                                            v-if="getFeaturedGalleryImages(destination)[0]"
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
                                            v-if="getFeaturedGalleryImages(destination)[1]"
                                            class="side-photo side-photo-top has-image"
                                            :style="{
                                                backgroundImage: `url('${getFeaturedGalleryImages(destination)[1]}')`,
                                            }"
                                        ></div>

                                        <div
                                            v-if="getFeaturedGalleryImages(destination)[2]"
                                            class="side-photo side-photo-bottom has-image"
                                            :style="{
                                                backgroundImage: `url('${getFeaturedGalleryImages(destination)[2]}')`,
                                            }"
                                        ></div>

                                        <div
                                            v-if="getExtraGalleryImages(destination).length"
                                            class="gallery-extra"
                                        >
                                            <div
                                                v-for="(image, index) in getExtraGalleryImages(destination)"
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
                                                    <h3>{{ destination.name }}</h3>
                                                    <p>{{ destination.category_label }}</p>
                                                </div>
                                                <div class="spot-card-icons">
                                                    <button type="button">♡</button>
                                                    <button type="button">↗</button>
                                                </div>
                                            </div>

                                            <p
                                                v-if="destination.average_rating"
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
                                                ({{ destination.ratings_count }}
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
                                                <button type="button" class="solid-btn">
                                                    Get Directions
                                                </button>
                                                <button type="button" class="ghost-pill">
                                                    Save
                                                </button>
                                            </div>
                                        </article>

                                        <article class="info-card">
                                            <div class="info-tabs">
                                                <button type="button" class="active">
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
                                                    <strong>Municipality</strong>
                                                    <span>Baao, Camarines Sur</span>
                                                </li>
                                                <li>
                                                    <strong>Coordinates</strong>
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

            <section class="section-shell reviews-section">
                <p class="section-pill section-pill-gold">
                    Ratings &amp; Reviews
                </p>
                <h2>What Visitors Say</h2>
                <p class="section-subtitle">
                    Read authentic reviews from travelers who have experienced
                    Baao
                </p>

                <div v-if="reviewSummary.total_reviews > 0" class="reviews-layout">
                    <aside class="rating-panel">
                        <h3>{{ reviewSummary.average_rating?.toFixed(1) ?? "—" }}</h3>
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
                            <strong>Tourism Mapping System</strong>
                            <small>Municipality of Baao</small>
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
                        <li><a href="#map">Map</a></li>
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
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap");

.tourism-page {
    --bg-base: #f8fafc;
    --bg-surface: #ffffff;
    --bg-glass: rgba(255, 255, 255, 0.85);
    --border-glass: #e2e8f0;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --accent-primary: #10b981;
    --accent-glow: rgba(16, 185, 129, 0.25);
    --accent-sec: #0284c7;
    --gradient-hero: linear-gradient(135deg, #059669 0%, #10b981 100%);

    background-color: var(--bg-base);
    color: var(--text-main);
    font-family: "Plus Jakarta Sans", sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.top-nav-shell {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 1000;
    background: transparent;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border-bottom: 1px solid transparent;
    transition: all 0.35s ease;
}
.top-nav-shell.nav-scrolled {
    background: var(--bg-glass);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border-glass);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
}

.top-nav-shell.mobile-menu-open {
    background: #ffffff;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    border-bottom: 1px solid var(--border-glass);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
}

.top-nav-shell.mobile-menu-open .brand {
    color: var(--text-main);
}
.top-nav-shell.mobile-menu-open .hamburger-btn span {
    background: var(--text-main);
}

/* Transparent nav: white text */
.top-nav-shell:not(.nav-scrolled) .nav-link {
    color: rgba(255, 255, 255, 0.8);
}
.top-nav-shell:not(.nav-scrolled) .nav-link:hover {
    color: #ffffff;
}
.top-nav-shell:not(.nav-scrolled) .brand {
    color: #ffffff;
}
.top-nav-shell:not(.nav-scrolled) .brand-copy small {
    color: rgba(255, 255, 255, 0.7);
}
.top-nav-shell:not(.nav-scrolled) .ghost-btn,
.top-nav-shell:not(.nav-scrolled) .downloads-trigger {
    color: #ffffff;
}
.top-nav-shell:not(.nav-scrolled) .downloads-chevron {
    border-top-color: rgba(255, 255, 255, 0.85);
}
.top-nav-shell:not(.nav-scrolled):not(.mobile-menu-open) .hamburger-btn span {
    background: #ffffff;
}

.top-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.65rem 1rem;
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
}

.brand-copy {
    display: flex;
    flex-direction: column;
}

.brand-copy strong {
    display: block;
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.brand-copy small {
    color: var(--accent-primary);
    font-weight: 700;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.nav-links {
    display: flex;
    gap: 2rem;
}

.nav-link {
    color: var(--text-muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.85rem;
    transition: color 0.2s ease;
    position: relative;
}

.nav-link:hover {
    color: var(--text-main);
}
.nav-link::after {
    content: "";
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--accent-primary);
    transition: width 0.3s ease;
}
.nav-link:hover::after {
    width: 100%;
}

.auth-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.downloads-dropdown {
    position: relative;
}

.downloads-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: none;
    border: none;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: color 0.2s ease;
    padding: 0;
    font-family: inherit;
}

.downloads-trigger:hover {
    color: var(--text-main);
}

.downloads-chevron {
    display: inline-block;
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid var(--text-muted);
    transition: transform 0.2s ease, border-top-color 0.2s ease;
}

.downloads-chevron.open {
    transform: rotate(180deg);
}

.downloads-menu {
    position: absolute;
    top: calc(100% + 0.75rem);
    right: 0;
    min-width: 15rem;
    padding: 0.5rem;
    background: var(--bg-glass);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--border-glass);
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
    z-index: 120;
}

.downloads-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 10px;
    text-decoration: none;
    color: var(--text-main);
    transition: background 0.2s ease;
}

.downloads-item:hover {
    background: rgba(16, 185, 129, 0.08);
}

.downloads-item-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    object-fit: cover;
    object-position: center top;
    flex-shrink: 0;
}

.downloads-item-copy {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.downloads-item-copy strong {
    font-size: 0.85rem;
    font-weight: 700;
}

.downloads-item-copy small {
    color: var(--text-muted);
    font-size: 0.72rem;
    font-weight: 600;
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
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    z-index: 110;
}
.hamburger-btn span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--text-main);
    border-radius: 2px;
    transition: all 0.3s ease;
    transform-origin: center;
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

.mobile-nav {
    display: none;
}

.ghost-btn {
    background: transparent;
    border: none;
    color: var(--text-main);
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: color 0.2s;
}
.ghost-btn:hover {
    color: var(--accent-primary);
}

.solid-btn {
    background: var(--gradient-hero);
    border: none;
    padding: 0.55rem 1.25rem;
    border-radius: 99px;
    color: white;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 15px var(--accent-glow);
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}
.solid-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px var(--accent-glow);
}

/* HERO SECTION CSS */
.hero-section {
    position: relative;
    padding: 9rem 2rem 7rem;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    overflow: hidden;
    background-size: cover;
    background-position: center;
    height: 90vh;
    min-height: 600px;
    transition: background-image 0.5s ease-in-out;
}
.hero-section::before {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 1;
}
.hero-section .hero-grid {
    opacity: 0.1;
}

.hero-pill {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    background: rgba(16, 185, 129, 0.1);
    color: var(--accent-primary);
    border-radius: 99px;
    border: 1px solid rgba(16, 185, 129, 0.2);
    font-weight: 700;
    font-size: 0.75rem;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.hero-content {
    width: 100%;
    max-width: 520px;
    margin-right: auto;
    text-align: left;
    color: white;
    z-index: 10;
    position: relative;
}
.hero-content h1 {
    color: white;
    font-size: clamp(2.8rem, 5vw, 4rem);
    line-height: 1.1;
    margin-bottom: 1.2rem;
    font-family: "Outfit", sans-serif;
    font-weight: 800;
    letter-spacing: -0.02em;
}
.hero-content h1 span {
    background: linear-gradient(135deg, #34d399 0%, #10b981 50%, #06b6d4 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero-subtitle {
    font-size: 1rem;
    margin-left: 0;
    color: #e2e8f0;
    margin-bottom: 2.5rem;
    line-height: 1.6;
}

.search-shell {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    border-radius: 99px;
    padding: 0.3rem;
    display: flex;
    align-items: center;
    max-width: 600px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    margin-left: 0;
}

.search-icon {
    font-size: 1.5rem;
    padding: 0 1rem;
    color: var(--text-muted);
}

.search-shell input {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--text-main);
    font-size: 0.9rem;
    outline: none;
}
.search-shell input::placeholder {
    color: #94a3b8;
}

.search-shell button {
    background: var(--text-main);
    color: white;
    border: none;
    padding: 0.5rem 1.75rem;
    border-radius: 99px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
}
.search-shell button:hover {
    background: var(--accent-primary);
}

.search-result {
    margin-top: 1rem;
    color: #e2e8f0;
    font-size: 0.9rem;
    font-weight: 600;
}

.hero-spots-carousel {
    position: absolute;
    bottom: 3rem;
    right: 0;
    display: flex;
    gap: 1.25rem;
    width: 100%;
    max-width: 55%;
    overflow-x: auto;
    padding: 2rem 2rem 1rem;
    align-items: flex-end;
    z-index: 20;
}

.hero-spots-carousel::-webkit-scrollbar {
    height: 6px;
}
.hero-spots-carousel::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 99px;
}

/* Carousel queue transition */
.carousel-queue-move {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.carousel-queue-enter-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.carousel-queue-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: absolute;
}
.carousel-queue-enter-from {
    opacity: 0;
    transform: translateX(80px) scale(0.9);
}
.carousel-queue-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.85);
}

.spot-card-mini {
    flex: 0 0 auto;
    width: 170px;
    height: 240px;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    box-shadow: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid rgba(255, 255, 255, 0.1);
    background: #0f172a;
}
.spot-card-mini:hover {
    transform: translateY(-8px);
}
.spot-card-mini-active {
    border-color: var(--accent-primary);
    transform: translateY(-10px);
    box-shadow: none;
}
.spot-card-mini img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
.spot-card-mini-info {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background: linear-gradient(
        to top,
        rgba(0, 0, 0, 0.85) 0%,
        transparent 100%
    );
    padding: 2.5rem 1rem 1rem;
    color: white;
    text-align: left;
    display: flex;
    flex-direction: column;
}
.spot-card-mini-info small {
    font-size: 0.65rem;
    color: #38bdf8;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 0.3rem;
}
.spot-card-mini-info strong {
    font-size: 0.85rem;
    font-weight: 800;
    line-height: 1.2;
    font-family: "Outfit";
}

@media (max-width: 1200px) and (min-width: 769px) {
    .hero-spots-carousel {
        max-width: 100%;
        right: 1rem;
        left: 1rem;
        padding-bottom: 1rem;
    }
    .hero-content {
        margin-bottom: 15rem;
    }
}

/* MAIN CONTENT AND LAYOUT CSS */

.main-content {
    --section-x: clamp(1.25rem, 4vw, 3rem);
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.section-shell {
    width: 100%;
    box-sizing: border-box;
    padding: clamp(3.5rem, 6vw, 5.5rem) var(--section-x);
    text-align: center;
    scroll-margin-top: 5.7rem;
}

.map-section {
    background: var(--bg-base);
}

.destination-section {
    background: var(--bg-surface);
    border-top: 1px solid var(--border-glass);
    border-bottom: 1px solid var(--border-glass);
}

.destination-swipe-shell {
    margin-top: 2rem;
    text-align: left;
}

.destination-swipe-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.destination-swipe-count {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-main);
}

.destination-swipe-hint {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text-muted);
}

.destination-swipe-stage {
    position: relative;
    margin-left: calc(-1 * var(--section-x));
    margin-right: calc(-1 * var(--section-x));
    width: calc(100% + 2 * var(--section-x));
}

.destination-swipe-arrow {
    display: none;
    position: absolute;
    top: 50%;
    z-index: 6;
    width: 3.25rem;
    height: 4.5rem;
    border: 1px solid var(--border-glass);
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    color: var(--text-main);
    font-size: 1.35rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
    transition:
        background 0.2s ease,
        color 0.2s ease,
        transform 0.2s ease;
}

.destination-swipe-arrow:hover {
    background: var(--text-main);
    color: white;
}

.destination-swipe-arrow-prev {
    left: 0;
    transform: translateY(-50%);
    border-radius: 0 999px 999px 0;
    border-left: none;
    padding-right: 0.35rem;
}

.destination-swipe-arrow-next {
    right: 0;
    transform: translateY(-50%);
    border-radius: 999px 0 0 999px;
    border-right: none;
    padding-left: 0.35rem;
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

.destination-swipe-dots {
    display: flex;
    justify-content: center;
    gap: 0.45rem;
    margin-top: 1.25rem;
}

.destination-swipe-dot {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: #cbd5e1;
    transition:
        width 0.25s ease,
        background 0.25s ease;
}

.destination-swipe-dot.active {
    width: 1.35rem;
    background: var(--accent-primary);
}

@media (min-width: 769px) {
    .destination-swipe-arrow {
        display: grid;
        place-items: center;
    }

    .destination-swipe-hint-mobile {
        display: none;
    }

    .destination-swipe-viewport {
        padding: 0 3.75rem;
        cursor: default;
        user-select: auto;
    }

    .destination-swipe-viewport:active {
        cursor: default;
    }
}

.reviews-section {
    background: var(--bg-base);
}

.events-section {
    background: var(--bg-surface);
    border-top: 1px solid var(--border-glass);
    padding-bottom: 0;
}
.section-pill {
    display: inline-block;
    padding: 0.4rem 1.2rem;
    border-radius: 99px;
    font-weight: 800;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}
.section-pill-peach {
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #ffedd5;
}
.section-pill-mint {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #d1fae5;
}
.section-pill-gold {
    background: #fef3c7;
    color: #d97706;
    border: 1px solid #fef08a;
}
.section-pill-blue {
    background: #f0f9ff;
    color: #0284c7;
    border: 1px solid #e0f2fe;
}

.section-shell h2 {
    font-family: "Outfit", sans-serif;
    font-size: clamp(2rem, 3.5vw, 2.8rem);
    margin: 0 0 1rem 0;
    color: var(--text-main);
    font-weight: 800;
    letter-spacing: -0.02em;
}
.section-subtitle {
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto;
    font-size: 0.95rem;
    line-height: 1.6;
}

.category-row {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin: 2rem 0;
}
.category-chip {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    color: var(--text-muted);
    padding: 0.6rem 1.5rem;
    border-radius: 99px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}
.category-chip:hover {
    border-color: var(--accent-primary);
    color: var(--accent-primary);
}
.category-chip-active {
    background: var(--text-main);
    color: white;
    border-color: var(--text-main);
    box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);
}

.map-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 1.5rem;
    margin-top: 1.5rem;
    text-align: left;
}

.spots-sidebar {
    background: transparent;
    border: none;
    border-top: 1px solid var(--border-glass);
    border-radius: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: none;
    max-height: 574px;
}

.sidebar-head {
    padding: 1.25rem 0;
    border-bottom: 1px solid var(--border-glass);
    background: transparent;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-head h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    font-family: "Outfit";
    color: var(--text-main);
}
.sidebar-head span {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-weight: 600;
}

.spot-list {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    flex: 1;
}

.spot-list li {
    padding: 1.15rem 0;
    border-bottom: 1px solid var(--border-glass);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: color 0.2s;
}

.spot-list li:hover {
    background: transparent;
    color: var(--accent-primary);
}

.spot-active {
    background: transparent !important;
    border-left: none !important;
    padding-left: 0 !important;
    color: var(--accent-primary);
}

.spot-info {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.spot-info strong {
    color: var(--text-main);
    font-weight: 700;
    font-size: 0.95rem;
}
.spot-info small {
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 600;
}

.spot-arrow {
    color: var(--border-glass);
    font-weight: 800;
    transition: color 0.2s;
}
.spot-active .spot-arrow {
    color: var(--accent-primary);
}

.map-card {
    position: relative;
    z-index: 0;
    isolation: isolate;
    background: transparent;
    border: none;
    border-radius: 0;
    overflow: visible;
    box-shadow: none;
}
.outline-card {
    margin-top: 0 !important;
}

.map-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 0 1rem;
    border-bottom: none;
    background: transparent;
}
.map-card-head h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
    font-family: "Outfit";
}
.map-legend {
    display: flex;
    gap: 1.5rem;
    list-style: none;
    padding: 0;
    margin: 0;
}
.map-legend li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 600;
}
.map-legend span {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
}
.map-canvas-wrap {
    padding: 0;
}
.leaflet-map {
    position: relative;
    z-index: 0;
    height: 500px;
    border-radius: 16px;
    width: 100%;
    border: 1px solid var(--border-glass);
}

.mobile-app-feature {
    display: grid;
    grid-template-columns: 1.1fr 1.4fr 0.8fr;
    gap: 2rem;
    align-items: center;
    margin-top: 2.5rem;
    padding: 2rem 0 0;
    text-align: left;
    background: transparent;
    border: none;
    border-top: 1px solid var(--border-glass);
    border-radius: 0;
    box-shadow: none;
}

.mobile-app-intro h3 {
    margin: 0 0 0.75rem;
    font-family: "Outfit", sans-serif;
    font-size: clamp(1.5rem, 2.5vw, 2rem);
    font-weight: 800;
    color: var(--text-main);
    letter-spacing: -0.02em;
}

.mobile-app-intro p {
    margin: 0 0 1.5rem;
    color: var(--text-muted);
    line-height: 1.7;
    font-size: 0.95rem;
}

.mobile-app-intro .section-pill {
    margin-bottom: 1rem;
}

.mobile-app-download-btn {
    display: inline-flex;
    text-decoration: none;
}

.mobile-app-feature-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 1rem;
}

.mobile-app-feature-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 1rem 0;
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--border-glass);
    border-radius: 0;
}

.mobile-app-feature-list li:last-child {
    border-bottom: none;
}

.mobile-app-feature-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 12px;
    background: var(--gradient-hero);
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px var(--accent-glow);
}

.mobile-app-feature-list strong {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
    color: var(--text-main);
}

.mobile-app-feature-list p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.85rem;
    line-height: 1.5;
}

.mobile-app-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0.5rem 0;
    background: transparent;
    border: none;
    border-radius: 0;
    box-shadow: none;
}

.mobile-app-preview-logo {
    width: 5.5rem;
    height: 5.5rem;
    border-radius: 50%;
    object-fit: cover;
    object-position: center top;
    margin-bottom: 1rem;
}

.mobile-app-preview strong {
    font-family: "Outfit", sans-serif;
    font-size: 1.35rem;
    color: var(--text-main);
}

.mobile-app-preview small {
    margin-top: 0.35rem;
    color: var(--accent-primary);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.mobile-app-version {
    margin-top: 1rem;
    padding: 0.35rem 0.85rem;
    border-radius: 99px;
    background: #f0f9ff;
    color: var(--accent-sec);
    font-size: 0.75rem;
    font-weight: 700;
}

.destination-layout {
    display: grid;
    grid-template-columns: 1.3fr 1fr;
    gap: 2rem;
    margin-top: 1.5rem;
    text-align: left;
}
.destination-gallery {
    display: grid;
    grid-template-columns: 2fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 1rem;
    min-height: 500px;
}
.destination-gallery.gallery-single {
    grid-template-columns: 1fr;
    grid-template-rows: minmax(420px, 1fr);
    min-height: 420px;
}
.destination-gallery.gallery-single .main-photo {
    grid-row: auto;
    min-height: 420px;
}
.destination-gallery.gallery-duo {
    grid-template-columns: 1.65fr 1fr;
    grid-template-rows: minmax(360px, 1fr);
    min-height: 360px;
}
.destination-gallery.gallery-duo .main-photo,
.destination-gallery.gallery-duo .side-photo-top {
    min-height: 360px;
}
.destination-gallery.gallery-duo .main-photo {
    grid-row: auto;
}
.main-photo,
.side-photo {
    border-radius: 20px;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: flex-end;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
.main-photo.has-image::before,
.side-photo.has-image::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.35), transparent);
    pointer-events: none;
}
.gallery-placeholder {
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: var(--text-muted);
    font-size: 0.95rem;
    font-weight: 600;
}
.main-photo {
    grid-row: span 2;
}
.side-photo-top,
.side-photo-bottom {
    min-height: 8rem;
}
.gallery-extra {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
    gap: 0.75rem;
}
.gallery-thumb {
    aspect-ratio: 1;
    border-radius: 14px;
    background-size: cover;
    background-position: center;
    border: 1px solid var(--border-glass);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}
.main-photo,
.side-photo {
    font-size: 1.15rem;
    font-weight: 800;
    color: white;
    position: relative;
}

.destination-side {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.spot-card,
.info-card {
    background: transparent;
    padding: 0;
    border-radius: 0;
    border: none;
    box-shadow: none;
}

.info-card {
    border-top: 1px solid var(--border-glass);
    padding-top: 1.5rem;
}
.spot-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.6rem;
}
.spot-card-head h3 {
    font-family: "Outfit", sans-serif;
    font-size: 2.25rem;
    line-height: 1.1;
    margin: 0 0 0.5rem 0;
    color: var(--text-main);
    font-weight: 800;
}
.spot-rating-empty {
    color: var(--text-muted);
}

.section-empty,
.review-empty,
.spot-empty {
    color: var(--text-muted);
    font-weight: 600;
    margin: 2rem auto 0;
    max-width: 36rem;
}

.review-location {
    color: var(--accent-primary);
    font-size: 0.85rem;
    font-weight: 700;
    margin: 0 0 0.75rem;
}

.spot-card-head p {
    color: var(--accent-primary);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-size: 0.85rem;
    margin: 0;
}
.spot-card-icons {
    display: flex;
    gap: 0.5rem;
}
.spot-card-icons button {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: var(--text-muted);
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 1.2rem;
    cursor: pointer;
    transition: all 0.2s;
}
.spot-card-icons button:hover {
    background: var(--text-main);
    color: white;
}

.spot-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1.5rem 0 0 0;
    color: var(--text-muted);
    font-size: 0.95rem;
    font-weight: 600;
}
.spot-rating span {
    background: #fef3c7;
    color: #d97706;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-weight: 800;
    display: inline-block;
    margin-right: 0.5rem;
}

.spot-description {
    color: var(--text-muted);
    line-height: 1.7;
    margin: 1.5rem 0 0 0;
    font-size: 1.05rem;
}

.spot-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
.spot-actions .solid-btn {
    flex: 1;
    padding: 1rem 0;
    font-size: 1rem;
    box-shadow: 0 4px 10px var(--accent-glow);
}
.spot-actions .ghost-pill {
    background: transparent;
    border: 1px solid var(--border-glass);
    color: var(--text-main);
    border-radius: 99px;
    padding: 0 2rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
}
.spot-actions .ghost-pill:hover {
    background: #f1f5f9;
}

.info-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-glass);
    margin-bottom: 2rem;
    column-gap: 0.4rem;
}
.info-tabs button {
    flex: 1;
    background: transparent;
    border: none;
    padding: 1rem 0;
    color: var(--text-muted);
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    position: relative;
}
.info-tabs button.active {
    color: var(--accent-primary);
}
.info-tabs button.active::after {
    content: "";
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background: var(--accent-primary);
}

.info-card ul {
    list-style: none;
    padding: 0;
    display: grid;
    gap: 1.5rem;
    margin: 0;
}
.info-card li {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}
.info-card strong {
    color: var(--text-main);
    font-weight: 800;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.info-card span {
    color: var(--text-muted);
    font-size: 1.05rem;
    font-weight: 500;
}

.reviews-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-top: 3rem;
    text-align: left;
}
.rating-panel {
    background: transparent;
    padding: 0;
    border-radius: 0;
    border: none;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    text-align: left;
    box-shadow: none;
}
.rating-panel h3 {
    font-family: "Outfit", sans-serif;
    font-size: 4.5rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
}
.rating-panel .stars {
    font-size: 1.5rem;
    color: #f59e0b;
    margin: 0.5rem 0;
    letter-spacing: 0.2em;
    text-align: left;
}
.rating-panel small {
    color: var(--text-muted);
    font-weight: 600;
    margin-bottom: 2rem;
    display: block;
    text-align: left;
}
.rating-panel ul {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}
.rating-panel li {
    display: grid;
    grid-template-columns: 2rem 1fr 1.8rem;
    align-items: center;
    gap: 1rem;
    color: var(--text-muted);
    font-weight: 600;
}
.rating-panel b {
    height: 6px;
    background: #f1f5f9;
    border-radius: 99px;
    overflow: hidden;
    display: block;
}
.rating-panel b i {
    height: 100%;
    background: #f59e0b;
    display: block;
    border-radius: 99px;
}
.rating-panel .solid-btn {
    width: 100%;
    margin-top: 0.9rem;
}

.review-stack {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.review-card {
    background: transparent;
    padding: 1.5rem 0;
    border-radius: 0;
    border: none;
    border-bottom: 1px solid var(--border-glass);
    margin: 0;
    text-align: left;
    box-shadow: none;
}

.review-stack .review-card:last-child {
    border-bottom: none;
}
.review-head {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: var(--gradient-hero);
    display: grid;
    place-items: center;
    font-weight: 700;
    font-size: 1rem;
    color: white;
}
.review-head div {
    flex: 1;
}
.review-head h4 {
    color: var(--text-main);
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0 0 0.2rem 0;
}
.review-head small {
    color: var(--text-muted);
    font-size: 0.85rem;
    font-weight: 600;
}
.review-head p {
    color: #f59e0b;
    letter-spacing: 0.1em;
    margin: 0;
}
.review-card > p {
    color: var(--text-muted);
    line-height: 1.6;
    margin: 1.25rem 0 0 0;
    font-size: 1rem;
    font-weight: 500;
}
.review-card footer {
    border-top: 1px solid var(--border-glass);
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    display: flex;
    gap: 1.5rem;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.9rem;
}
.load-more {
    background: transparent;
    border: 1px solid var(--border-glass);
    color: var(--text-main);
    padding: 1rem;
    border-radius: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
    text-align: center;
    display: block;
    width: 100%;
}
.load-more:hover {
    background: #f8fafc;
}

.events-section .section-head-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    text-align: left;
    margin-bottom: 3rem;
}
.events-section .section-head-row div {
    max-width: 600px;
}
.events-section .section-head-row .section-pill {
    margin-bottom: 1rem;
    display: inline-block;
    margin-left: 0;
}
.events-section .section-head-row h2 {
    font-family: "Outfit", sans-serif;
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    margin: 0 0 1rem 0;
    color: var(--text-main);
    font-weight: 800;
}
.events-section .section-head-row .section-subtitle {
    margin: 0;
    width: auto;
}
.events-section .ghost-pill {
    background: var(--bg-surface);
    border: 1px solid var(--border-glass);
    color: var(--text-main);
    border-radius: 99px;
    padding: 0.6rem 1.5rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}
.events-section .ghost-pill:hover {
    background: #f1f5f9;
}

.event-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 300px));
    gap: 0;
    margin-top: 2rem;
    justify-content: start;
}
.event-card {
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--border-glass);
    border-left: 4px solid transparent;
    border-radius: 0;
    padding: 1.5rem 0 1.5rem 1rem;
    text-align: left;
    position: relative;
    overflow: visible;
    transition: color 0.2s ease;
    margin: 0;
    box-shadow: none;
}
.event-card:hover {
    transform: none;
    box-shadow: none;
}

.event-card::before {
    display: none;
}
.event-orange {
    border-left-color: #ea580c;
}
.event-teal {
    border-left-color: #059669;
}
.event-gold {
    border-left-color: #d97706;
}
.event-blue {
    border-left-color: #0284c7;
}

.event-type {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--accent-sec);
    margin: 0 0 0.75rem 0;
    padding-top: 0;
}
.event-orange .event-type {
    color: #ea580c;
}
.event-gold .event-type {
    color: #d97706;
}
.event-blue .event-type {
    color: #0284c7;
}

.event-card h3 {
    font-family: "Outfit";
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0 0 0.75rem 0;
    line-height: 1.3;
}
.event-card > p {
    color: var(--text-muted);
    line-height: 1.55;
    margin: 0 0 1.25rem 0;
    font-size: 0.88rem;
    font-weight: 500;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.event-card ul {
    list-style: none;
    border-top: 1px solid var(--border-glass);
    padding: 1rem 0 0 0;
    display: grid;
    gap: 0.5rem;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.82rem;
    margin: 0 0 1.25rem 0;
}
.event-card button {
    width: 100%;
    background: #f8fafc;
    border: 1px solid var(--border-glass);
    color: var(--text-main);
    padding: 0.75rem;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 0;
    font-size: 0.88rem;
}
.event-card button:hover {
    background: var(--text-main);
    color: white;
}

.newsletter {
    background: linear-gradient(135deg, #059669, #10b981);
    padding: clamp(3.5rem, 6vw, 5rem) var(--section-x);
    border-radius: 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-top: clamp(3rem, 5vw, 5rem);
    margin-left: calc(-1 * var(--section-x));
    margin-right: calc(-1 * var(--section-x));
    width: auto;
    box-shadow: none;
}
.newsletter::before {
    content: "";
    position: absolute;
    width: 400px;
    height: 400px;
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.15) 0%,
        transparent 70%
    );
    top: -200px;
    right: -200px;
    border-radius: 50%;
}
.newsletter h3 {
    font-family: "Outfit", sans-serif;
    font-size: 3rem;
    color: white;
    margin: 0 0 1rem 0;
    font-weight: 800;
}
.newsletter p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto 2.5rem;
    font-weight: 500;
}
.newsletter form {
    display: flex;
    gap: 1rem;
    max-width: 600px;
    margin: 0 auto;
    flex-wrap: wrap;
    justify-content: center;
}
.newsletter input {
    flex: 1;
    min-width: 250px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 1rem 1.5rem;
    color: white;
    font-size: 1rem;
    outline: none;
}
.newsletter input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}
.newsletter button {
    background: white;
    color: #059669;
    border: none;
    padding: 0 2rem;
    border-radius: 16px;
    font-weight: 800;
    cursor: pointer;
    transition: transform 0.2s;
}
.newsletter button:hover {
    transform: translateY(-2px);
}

.site-footer {
    --section-x: clamp(1.25rem, 4vw, 3rem);
    width: 100%;
    border-top: 1px solid var(--border-glass);
    padding: clamp(3.5rem, 6vw, 5rem) 0 clamp(1.5rem, 3vw, 2rem);
    background: var(--bg-surface);
    margin-top: 0;
}
.footer-shell {
    max-width: none;
    margin: 0;
    padding: 0 var(--section-x);
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 4rem;
}
.footer-shell .brand {
    display: inline-flex;
    margin-bottom: 1.5rem;
}
.footer-shell section p {
    color: var(--text-muted);
    line-height: 1.6;
    margin: 0 0 2rem 0;
    font-size: 0.95rem;
    font-weight: 500;
}
.social-row {
    display: flex;
    gap: 1rem;
    margin-top: 0.8rem;
}
.social-row a {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f1f5f9;
    display: grid;
    place-items: center;
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.2s;
    border: 1px solid var(--border-glass);
}
.social-row a:hover {
    background: var(--accent-primary);
    color: white;
    transform: translateY(-3px);
}
.footer-shell h4 {
    color: var(--text-main);
    font-size: 1.1rem;
    font-weight: 800;
    margin: 0 0 1.5rem 0;
    font-family: "Outfit";
}
.footer-shell ul {
    list-style: none;
    padding: 0;
    display: grid;
    gap: 1rem;
    margin: 0.65rem 0 0 0;
}
.footer-shell li,
.footer-shell a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color 0.2s;
    font-size: 0.95rem;
    display: flex;
    flex-direction: column;
    font-weight: 500;
}
.footer-shell a:hover {
    color: var(--accent-primary);
}
.footer-bottom {
    max-width: none;
    margin: 4rem 0 0;
    padding: 2rem var(--section-x) 0;
    border-top: 1px solid var(--border-glass);
    display: flex;
    justify-content: space-between;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 500;
}

:deep(.leaflet-control-attribution) {
    background: rgba(255, 255, 255, 0.8) !important;
    color: var(--text-muted) !important;
    font-size: 0.7rem;
}
:deep(.leaflet-control-attribution a) {
    color: var(--accent-sec) !important;
    font-weight: 700;
}
:deep(.leaflet-tooltip) {
    background: white;
    color: var(--text-main);
    border: 1px solid var(--border-glass);
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    font-family: "Plus Jakarta Sans", sans-serif;
    font-weight: 700;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

@media (max-width: 1024px) {
    .destination-layout,
    .reviews-layout {
        grid-template-columns: 1fr;
    }
    .destination-gallery {
        min-height: 400px;
    }
    .footer-shell {
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    .map-layout {
        grid-template-columns: 1fr;
    }
    .spots-sidebar {
        max-height: 350px;
    }
    .mobile-app-feature {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .mobile-app-preview {
        grid-column: auto;
        flex-direction: row;
        gap: 1.25rem;
        text-align: left;
        justify-content: center;
    }
    .mobile-app-preview-logo {
        margin-bottom: 0;
    }
    .event-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        justify-content: center;
    }
}

@media (max-width: 768px) {
    /* Hamburger button */
    .hamburger-btn {
        display: flex;
    }
    .nav-links {
        display: none;
    }
    .auth-actions {
        display: none;
    }

    /* Mobile nav drawer */
    .mobile-nav {
        display: flex;
        flex-direction: column;
        padding: 0.5rem 1.25rem 1.5rem;
        background: #ffffff;
        border-top: 1px solid var(--border-glass);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    }
    .mobile-nav-link {
        display: block;
        padding: 1rem 0.25rem;
        color: var(--text-main);
        font-weight: 600;
        font-size: 1.05rem;
        text-decoration: none;
        border-bottom: 1px solid #f1f5f9;
        transition: color 0.2s, padding-left 0.2s;
    }
    .mobile-nav-link:hover {
        color: var(--accent-primary);
        padding-left: 0.5rem;
    }
    .mobile-section-label {
        margin: 1.25rem 0 0.65rem;
        color: var(--text-muted);
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .mobile-download-card {
        margin-top: 0.25rem;
    }
    .mobile-download-link {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem;
        text-decoration: none;
        color: var(--text-main);
        background: linear-gradient(
            135deg,
            rgba(2, 132, 199, 0.08) 0%,
            rgba(16, 185, 129, 0.1) 100%
        );
        border: 1px solid rgba(16, 185, 129, 0.18);
        border-radius: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .mobile-download-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.12);
    }
    .mobile-download-copy {
        flex: 1;
        min-width: 0;
    }
    .mobile-download-copy strong {
        display: block;
        font-size: 0.98rem;
        font-weight: 700;
        line-height: 1.3;
    }
    .mobile-download-copy small {
        display: block;
        margin-top: 0.2rem;
        color: var(--accent-primary);
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .mobile-download-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        object-fit: cover;
        object-position: center top;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
    }
    .mobile-download-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        background: var(--gradient-hero);
        color: white;
        font-size: 1rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    .mobile-auth {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding-top: 0.25rem;
    }
    .mobile-login-btn,
    .mobile-auth .solid-btn {
        flex: 1;
        text-align: center;
        padding: 0.85rem 1rem;
        border-radius: 99px;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
    }
    .mobile-login-btn {
        background: #ffffff;
        color: var(--text-main);
        border: 1px solid var(--border-glass);
        text-decoration: none;
        display: block;
        transition: border-color 0.2s ease, color 0.2s ease;
    }
    .mobile-login-btn:hover {
        border-color: var(--accent-primary);
        color: var(--accent-primary);
    }
    .mobile-auth .solid-btn {
        border: none;
    }

    /* Mobile menu transition */
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

    /* Top nav */
    .top-nav {
        padding: 0.75rem 1rem;
    }
    .brand-copy {
        display: none;
    }
    .brand-icon {
        width: 2.5rem;
        height: 2.5rem;
    }

    /* Hero section */
    .hero-section {
        flex-direction: column;
        align-items: stretch;
        justify-content: flex-start;
        height: auto;
        min-height: auto;
        padding: 6.5rem 1.25rem 2rem;
        gap: 1.5rem;
        overflow: visible;
    }
    .hero-section::before {
        background: linear-gradient(
            180deg,
            rgba(15, 23, 42, 0.45) 0%,
            rgba(15, 23, 42, 0.22) 45%,
            rgba(15, 23, 42, 0.08) 100%
        );
    }
    .hero-content {
        max-width: 100%;
        margin-right: 0;
        margin-bottom: 0;
    }
    .hero-content h1 {
        font-size: 2.2rem;
    }
    .hero-subtitle {
        font-size: 1rem;
        margin-bottom: 1.25rem;
    }
    .hero-pill {
        font-size: 0.7rem;
        padding: 0.4rem 0.9rem;
        margin-bottom: 1rem;
    }
    .search-shell {
        width: 100%;
        max-width: 100%;
    }
    .search-shell input {
        font-size: 0.85rem;
        min-width: 0;
    }
    .search-shell button {
        padding: 0.65rem 1rem;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .search-result {
        font-size: 0.8rem;
        margin-top: 0.75rem;
    }

    /* Hero carousel on mobile */
    .hero-spots-carousel {
        position: relative;
        bottom: auto;
        left: auto;
        right: auto;
        max-width: 100%;
        width: 100%;
        margin-top: 0;
        padding: 0.25rem 0 0;
        gap: 0.75rem;
        align-items: flex-end;
        overflow-x: auto;
        overflow-y: visible;
    }
    .spot-card-mini {
        width: 140px;
        height: 200px;
        border-radius: 12px;
    }
    .spot-card-mini:hover,
    .spot-card-mini-active {
        transform: none;
    }
    .spot-card-mini-active {
        border-width: 3px;
        box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.35);
    }
    .spot-card-mini-info {
        padding: 1.5rem 0.75rem 0.75rem;
    }
    .spot-card-mini-info small {
        font-size: 0.55rem;
    }
    .spot-card-mini-info strong {
        font-size: 0.8rem;
    }

    /* Main content */
    .main-content {
        --section-x: 1rem;
    }

    .section-shell {
        padding-top: 2.5rem;
        padding-bottom: 2.5rem;
    }

    .events-section {
        padding-bottom: 0;
    }

    /* Section headings */
    .section-shell h2 {
        font-size: 1.8rem;
    }
    .section-subtitle {
        font-size: 0.95rem;
    }
    .section-pill {
        font-size: 0.7rem;
    }

    /* Category chips */
    .category-row {
        gap: 0.5rem;
        margin: 1.25rem 0;
    }
    .category-chip {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    /* Map section */
    .map-card-head {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
    }
    .map-legend {
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .leaflet-map {
        height: 350px;
    }
    .map-canvas-wrap {
        padding: 0.5rem;
    }
    .spots-sidebar {
        max-height: 280px;
    }
    .sidebar-head {
        padding: 1rem;
    }
    .spot-list li {
        padding: 1rem;
    }
    .mobile-app-feature {
        grid-template-columns: 1fr;
        padding: 1.5rem;
        gap: 1.25rem;
    }
    .mobile-app-preview {
        flex-direction: column;
        text-align: center;
    }
    .mobile-app-preview-logo {
        margin-bottom: 1rem;
    }
    .mobile-app-download-btn {
        width: 100%;
        justify-content: center;
    }

    /* Destination section */
    .destination-swipe-meta {
        flex-direction: column;
        align-items: flex-start;
    }
    .destination-gallery {
        grid-template-columns: 1fr;
        grid-template-rows: auto;
        min-height: auto;
        gap: 0.75rem;
    }
    .destination-gallery.gallery-duo {
        grid-template-columns: 1fr;
        grid-template-rows: auto;
    }
    .destination-gallery.gallery-single {
        grid-template-rows: auto;
        min-height: auto;
    }
    .destination-gallery.gallery-single .main-photo,
    .destination-gallery.gallery-duo .main-photo,
    .destination-gallery.gallery-duo .side-photo-top {
        min-height: 14rem;
    }
    .main-photo {
        grid-row: auto;
        min-height: 14rem;
    }
    .side-photo {
        min-height: 8rem;
    }
    .gallery-extra {
        grid-template-columns: repeat(auto-fill, minmax(72px, 1fr));
    }
    .spot-card-head {
        flex-direction: column;
    }
    .spot-card-head h3 {
        font-size: 1.6rem;
    }
    .spot-actions {
        flex-direction: column;
    }
    .spot-actions .ghost-pill {
        text-align: center;
        padding: 0.85rem;
    }

    /* Reviews */
    .rating-panel h3 {
        font-size: 3.5rem;
    }

    .review-card {
        padding: 1.25rem 0;
    }

    /* Events */
    .events-section .section-head-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    .events-section .section-head-row h2 {
        font-size: 1.8rem;
    }
    .event-grid {
        grid-template-columns: 1fr;
        justify-content: stretch;
    }
    .event-card {
        padding: 1.25rem 0 1.25rem 0.85rem;
    }

    /* Newsletter */
    .newsletter {
        padding: 3rem var(--section-x);
        margin-top: 2rem;
    }
    .newsletter h3 {
        font-size: 1.8rem;
    }
    .newsletter p {
        font-size: 0.95rem;
    }
    .newsletter form {
        flex-direction: column;
    }
    .newsletter input {
        min-width: unset;
    }
    .newsletter button {
        padding: 0.85rem 2rem;
    }

    /* Footer */
    .footer-shell {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    .site-footer {
        padding: 3rem 0 1.5rem;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 5.5rem max(1rem, env(safe-area-inset-left, 0)) 1.5rem;
        gap: 1.25rem;
    }
    .hero-content h1 {
        font-size: 1.85rem;
    }
    .hero-subtitle {
        font-size: 0.95rem;
        margin-bottom: 1rem;
    }
    .search-shell {
        flex-wrap: wrap;
        border-radius: 14px;
    }
    .search-shell button {
        width: 100%;
        margin-top: 0.35rem;
        min-height: 44px;
    }
    .spot-card-mini {
        width: 132px;
        height: 188px;
    }
    .spot-card-mini-info strong {
        font-size: 0.7rem;
    }
    .section-shell {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
    .section-shell h2 {
        font-size: 1.45rem;
    }
    .spot-card-head h3 {
        font-size: 1.35rem;
    }
    .rating-panel h3 {
        font-size: 2.75rem;
    }
    .newsletter h3 {
        font-size: 1.45rem;
    }
    .destination-swipe-count {
        font-size: 0.82rem;
    }
    .destination-swipe-hint-mobile {
        font-size: 0.76rem;
    }
}

/* Mobile UX polish — all sections & cards */
@media (max-width: 768px) {
    .tourism-page {
        -webkit-tap-highlight-color: transparent;
    }

    .top-nav-shell {
        padding-top: env(safe-area-inset-top, 0);
    }

    .section-shell {
        padding-left: max(1rem, env(safe-area-inset-left, 0));
        padding-right: max(1rem, env(safe-area-inset-right, 0));
    }

    .section-subtitle {
        padding-inline: 0.15rem;
        line-height: 1.65;
    }

    .section-empty {
        padding-inline: 0.5rem;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    /* Map section */
    .map-layout {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1.25rem;
    }

    .map-card {
        order: 1;
    }

    .spots-sidebar {
        order: 2;
        max-height: min(320px, 42vh);
    }

    .spot-list li {
        min-height: 44px;
        align-items: center;
    }

    .category-row {
        flex-wrap: nowrap;
        justify-content: flex-start;
        overflow-x: auto;
        overscroll-behavior-x: contain;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
        margin-inline: calc(-1 * var(--section-x));
        padding-inline: var(--section-x);
        padding-bottom: 0.35rem;
        scrollbar-width: none;
    }

    .category-row::-webkit-scrollbar {
        display: none;
    }

    .category-chip {
        flex-shrink: 0;
        scroll-snap-align: start;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
    }

    .mobile-app-feature {
        margin-top: 1.5rem;
    }

    .mobile-app-feature-list li {
        align-items: flex-start;
    }

    /* Destination swipe */
    .destination-swipe-shell {
        margin-top: 1.25rem;
    }

    .destination-swipe-stage {
        margin-inline: calc(-1 * var(--section-x));
        width: calc(100% + 2 * var(--section-x));
    }

    .destination-swipe-viewport {
        padding: 0;
    }

    .destination-slide {
        padding-inline: var(--section-x);
        box-sizing: border-box;
    }

    .destination-layout {
        margin-top: 0;
        gap: 1.15rem;
    }

    .spot-card-head {
        gap: 0.75rem;
    }

    .spot-card-icons button {
        width: 44px;
        height: 44px;
    }

    .spot-actions {
        gap: 0.75rem;
    }

    .spot-actions .solid-btn,
    .spot-actions .ghost-pill {
        width: 100%;
        min-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .gallery-extra {
        grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
        gap: 0.5rem;
    }

    .destination-swipe-dots {
        margin-top: 1rem;
    }

    /* Reviews */
    .reviews-layout {
        gap: 1.15rem;
        margin-top: 2rem;
    }

    .review-head {
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .review-head p {
        width: 100%;
        margin: 0.25rem 0 0;
    }

    .rating-panel .solid-btn {
        min-height: 46px;
    }

    /* Events */
    .events-section .section-head-row {
        margin-bottom: 1.25rem;
        gap: 0.85rem;
    }

    .events-section .ghost-pill {
        width: 100%;
        min-height: 44px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .event-grid {
        gap: 0;
        margin-top: 1.25rem;
        grid-template-columns: 1fr;
    }

    .event-card button {
        min-height: 44px;
    }

    /* Newsletter */
    .newsletter form {
        width: 100%;
        max-width: 100%;
    }

    .newsletter input,
    .newsletter button {
        width: 100%;
        min-height: 46px;
    }

    /* Footer */
    .footer-shell {
        padding-inline: max(1rem, env(safe-area-inset-left, 0))
            max(1rem, env(safe-area-inset-right, 0));
    }

    .footer-bottom {
        padding-inline: max(1rem, env(safe-area-inset-left, 0))
            max(1rem, env(safe-area-inset-right, 0));
    }

    .social-row a {
        width: 44px;
        height: 44px;
    }
}

@media (max-width: 640px) {
    .event-grid {
        grid-template-columns: 1fr;
    }

    .hero-content h1 {
        font-size: 2rem;
    }

    .leaflet-map {
        height: min(320px, 52vh);
    }

    .main-photo,
    .destination-gallery.gallery-single .main-photo {
        min-height: 12rem;
    }

    .side-photo {
        min-height: 7rem;
    }
}
</style>
