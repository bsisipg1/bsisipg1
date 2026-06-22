<script setup>
import { Link } from "@inertiajs/vue3";
import AdminLayout from "../../layouts/AdminLayout.vue";
import { useFlashToast } from "../../composables/useFlashToast";

const props = defineProps({
    locations: {
        type: Array,
        default: () => [],
    },
});

useFlashToast();

const totalReviews = props.locations.reduce(
    (count, location) => count + location.reviews.length,
    0,
);

const initials = (name) => {
    const parts = String(name ?? "")
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return "?";
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
};

const starLabel = (rating) => `${rating} out of 5 stars`;
</script>

<template>
    <AdminLayout
        title="Reviews"
        description="Read ratings and comments submitted by mobile app users for tourism locations."
    >
        <div class="page-toolbar">
            <div class="toolbar-copy">
                <h2>App User Reviews</h2>
                <p>
                    {{ locations.length }} location(s) · {{ totalReviews }}
                    review(s)
                </p>
            </div>
        </div>

        <section v-if="locations.length" class="reviews-list">
            <article
                v-for="location in locations"
                :key="location.id"
                class="location-card"
            >
                <header class="location-header">
                    <img
                        :src="location.image_url"
                        :alt="location.name"
                        class="location-thumb"
                    />
                    <div class="location-meta">
                        <div class="location-title-row">
                            <h3>{{ location.name }}</h3>
                            <span class="category-pill">{{
                                location.category_label
                            }}</span>
                        </div>
                        <p class="location-summary">
                            <span class="rating-badge">
                                ★ {{ location.average_rating }}
                            </span>
                            {{ location.ratings_count }}
                            {{
                                location.ratings_count === 1
                                    ? "review"
                                    : "reviews"
                            }}
                        </p>
                    </div>
                    <Link
                        :href="`/admin/locations/${location.id}`"
                        class="view-location-link"
                    >
                        View location
                    </Link>
                </header>

                <div class="reviews-table-shell">
                    <table class="reviews-table">
                        <thead>
                            <tr>
                                <th>App User</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="review in location.reviews"
                                :key="review.id"
                            >
                                <td>
                                    <div class="user-cell">
                                        <img
                                            v-if="review.user.profile_photo_url"
                                            :src="review.user.profile_photo_url"
                                            :alt="review.user.name"
                                            class="user-avatar"
                                        />
                                        <span
                                            v-else
                                            class="user-avatar placeholder"
                                        >
                                            {{ initials(review.user.name) }}
                                        </span>
                                        <strong class="user-name">{{
                                            review.user.name
                                        }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="stars"
                                        :aria-label="starLabel(review.rating)"
                                    >
                                        <span
                                            v-for="star in 5"
                                            :key="star"
                                            class="star"
                                            :class="{
                                                filled: star <= review.rating,
                                            }"
                                        >
                                            ★
                                        </span>
                                    </span>
                                </td>
                                <td class="comment-cell">
                                    {{
                                        review.comment?.trim()
                                            ? review.comment
                                            : "No comment provided"
                                    }}
                                </td>
                                <td>{{ review.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section v-else class="empty-state">
            <div class="empty-icon">★</div>
            <h3>No reviews yet</h3>
            <p>
                Reviews will appear here when app users rate and comment on
                tourism locations in the mobile app.
            </p>
        </section>
    </AdminLayout>
</template>

<style scoped>
.page-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem 1.1rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
}

.toolbar-copy h2 {
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.toolbar-copy p {
    margin-top: 0.2rem;
    font-size: 0.82rem;
    color: #64748b;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.location-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    overflow: hidden;
}

.location-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.location-thumb {
    width: 4rem;
    height: 2.75rem;
    object-fit: cover;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.location-meta {
    flex: 1;
    min-width: 0;
}

.location-title-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.location-title-row h3 {
    font-family: "Outfit", sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

.category-pill {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
}

.location-summary {
    margin-top: 0.25rem;
    font-size: 0.82rem;
    color: #64748b;
}

.rating-badge {
    display: inline-block;
    margin-right: 0.35rem;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
    font-weight: 700;
}

.view-location-link {
    flex-shrink: 0;
    padding: 0.45rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.45rem;
    color: #0f172a;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    transition:
        border-color 0.2s ease,
        color 0.2s ease;
}

.view-location-link:hover {
    border-color: #0284c7;
    color: #0284c7;
}

.reviews-table-shell {
    overflow-x: auto;
}

.reviews-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 44rem;
}

.reviews-table th,
.reviews-table td {
    padding: 0.85rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.reviews-table tbody tr:last-child td {
    border-bottom: none;
}

.reviews-table th {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
    background: #ffffff;
}

.reviews-table tbody tr:hover {
    background: rgba(16, 185, 129, 0.03);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 999px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.user-avatar.placeholder {
    display: grid;
    place-items: center;
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    font-size: 0.78rem;
    font-weight: 800;
}

.user-name {
    font-size: 0.86rem;
    color: #0f172a;
}

.stars {
    display: inline-flex;
    gap: 0.1rem;
}

.star {
    color: #cbd5e1;
    font-size: 0.95rem;
}

.star.filled {
    color: #f59e0b;
}

.comment-cell {
    max-width: 24rem;
    font-size: 0.84rem;
    color: #475569;
    line-height: 1.45;
    white-space: pre-wrap;
}

.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 0.85rem;
}

.empty-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 1rem;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.14);
    color: #b45309;
    font-size: 1.35rem;
    font-weight: 700;
}

.empty-state h3 {
    font-family: "Outfit", sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}

.empty-state p {
    margin: 0.55rem auto 0;
    max-width: 26rem;
    font-size: 0.9rem;
    color: #64748b;
}

@media (max-width: 720px) {
    .location-header {
        flex-wrap: wrap;
    }

    .view-location-link {
        width: 100%;
        text-align: center;
    }
}
</style>
