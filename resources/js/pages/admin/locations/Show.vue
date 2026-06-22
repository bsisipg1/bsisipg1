<script setup>
import { Link, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { useFlashToast } from "../../../composables/useFlashToast";

const props = defineProps({
    location: {
        type: Object,
        required: true,
    },
});

useFlashToast();

const deleteLocation = () => {
    if (
        !confirm(
            `Delete "${props.location.name}"? This action cannot be undone.`,
        )
    ) {
        return;
    }

    router.delete(`/admin/locations/${props.location.id}`, {
        onError: () => {
            toast.error("Unable to delete location. Please try again.");
        },
    });
};
</script>

<template>
    <AdminLayout
        title="View Location"
        description="Official record details for this tourism location."
    >
        <div class="view-shell">
            <div class="view-header">
                <Link href="/admin/locations" class="back-link">
                    ← Back to locations
                </Link>
                <div class="view-actions">
                    <Link
                        :href="`/admin/locations/${location.id}/edit`"
                        class="edit-btn"
                    >
                        Edit
                    </Link>
                    <button type="button" class="delete-btn" @click="deleteLocation">
                        Delete
                    </button>
                </div>
            </div>

            <div class="view-grid">
                <div class="image-panel">
                    <img
                        :src="location.image_url"
                        :alt="location.name"
                        class="location-image"
                    />
                </div>

                <div class="details-panel">
                    <span class="category-pill">{{ location.category_label }}</span>
                    <h2>{{ location.name }}</h2>
                    <p class="description">{{ location.description }}</p>

                    <dl class="meta-grid">
                        <div>
                            <dt>Latitude</dt>
                            <dd>{{ location.latitude }}</dd>
                        </div>
                        <div>
                            <dt>Longitude</dt>
                            <dd>{{ location.longitude }}</dd>
                        </div>
                        <div>
                            <dt>Created</dt>
                            <dd>{{ location.created_at }}</dd>
                        </div>
                        <div>
                            <dt>Last Updated</dt>
                            <dd>{{ location.updated_at }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <section
                v-if="location.gallery?.length"
                class="gallery-section"
            >
                <h3>Gallery</h3>
                <div class="gallery-grid">
                    <article
                        v-for="item in location.gallery"
                        :key="item.id"
                        class="gallery-card"
                    >
                        <img
                            v-if="item.type === 'image'"
                            :src="item.url"
                            :alt="`${location.name} gallery`"
                        />
                        <video
                            v-else
                            :src="item.url"
                            controls
                            playsinline
                            preload="metadata"
                        />
                        <span class="gallery-type">{{ item.type }}</span>
                    </article>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
.view-shell {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    overflow: hidden;
}

.view-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.back-link {
    font-size: 0.82rem;
    font-weight: 600;
    color: #0284c7;
    text-decoration: none;
}

.view-actions {
    display: flex;
    gap: 0.5rem;
}

.edit-btn,
.delete-btn {
    border-radius: 0.55rem;
    padding: 0.5rem 0.85rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}

.edit-btn {
    border: 1px solid #10b981;
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
}

.delete-btn {
    border: 1px solid #fca5a5;
    background: rgba(220, 38, 38, 0.06);
    color: #dc2626;
}

.view-grid {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 0;
}

.image-panel {
    background: #f1f5f9;
    border-right: 1px solid #e2e8f0;
}

.location-image {
    display: block;
    width: 100%;
    min-height: 100%;
    max-height: 28rem;
    object-fit: cover;
}

.details-panel {
    padding: 1.25rem;
}

.category-pill {
    display: inline-block;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
}

.details-panel h2 {
    margin-top: 0.65rem;
    font-family: "Outfit", sans-serif;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
}

.description {
    margin-top: 0.75rem;
    font-size: 0.92rem;
    line-height: 1.65;
    color: #64748b;
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.meta-grid dt {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
}

.meta-grid dd {
    margin-top: 0.15rem;
    font-size: 0.88rem;
    font-weight: 600;
    color: #0f172a;
}

.gallery-section {
    padding: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.gallery-section h3 {
    font-family: "Outfit", sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: 0.85rem;
    margin-top: 0.85rem;
}

.gallery-card {
    position: relative;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    overflow: hidden;
    background: #f8fafc;
}

.gallery-card img,
.gallery-card video {
    display: block;
    width: 100%;
    height: 10rem;
    object-fit: cover;
    background: #0f172a;
}

.gallery-type {
    position: absolute;
    top: 0.45rem;
    left: 0.45rem;
    padding: 0.18rem 0.45rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.72);
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
}

@media (max-width: 900px) {
    .view-grid {
        grid-template-columns: 1fr;
    }

    .image-panel {
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
    }
}
</style>
