<script setup>
import { Link, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { useFlashToast } from "../../../composables/useFlashToast";

defineProps({
    locations: {
        type: Array,
        default: () => [],
    },
});

useFlashToast();

const deleteLocation = (location) => {
    if (
        !confirm(
            `Delete "${location.name}"? This action cannot be undone.`,
        )
    ) {
        return;
    }

    router.delete(`/admin/locations/${location.id}`, {
        onError: () => {
            toast.error("Unable to delete location. Please try again.");
        },
    });
};
</script>

<template>
    <AdminLayout
        title="Locations"
        description="Manage registered tourism locations across the Municipality of Baao."
    >
        <div class="page-toolbar">
            <div class="toolbar-copy">
                <h2>Registered Locations</h2>
                <p>{{ locations.length }} location(s) on record</p>
            </div>
            <Link href="/admin/locations/create" class="create-btn">
                + Create Location
            </Link>
        </div>

        <section v-if="locations.length" class="table-shell">
            <div class="table-scroll">
                <table class="locations-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="location in locations" :key="location.id">
                            <td>
                                <img
                                    :src="location.image_url"
                                    :alt="location.name"
                                    class="table-thumb"
                                />
                            </td>
                            <td>
                                <strong class="location-name">{{
                                    location.name
                                }}</strong>
                                <p class="location-snippet">
                                    {{ location.description }}
                                </p>
                            </td>
                            <td>
                                <span class="category-pill">{{
                                    location.category_label
                                }}</span>
                            </td>
                            <td>{{ location.latitude }}</td>
                            <td>{{ location.longitude }}</td>
                            <td>{{ location.created_at }}</td>
                            <td>
                                <div class="action-group">
                                    <Link
                                        :href="`/admin/locations/${location.id}`"
                                        class="action-btn view"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="`/admin/locations/${location.id}/edit`"
                                        class="action-btn edit"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="action-btn delete"
                                        @click="deleteLocation(location)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-else class="empty-state">
            <div class="empty-icon">⌖</div>
            <h3>No locations yet</h3>
            <p>
                Start by creating the first official tourism location for Baao.
            </p>
            <Link href="/admin/locations/create" class="create-btn">
                + Create Location
            </Link>
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

.create-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 1rem;
    border-radius: 0.6rem;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: #ffffff;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 700;
    white-space: nowrap;
}

.table-shell {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    overflow: hidden;
}

.table-scroll {
    overflow-x: auto;
}

.locations-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 56rem;
}

.locations-table th,
.locations-table td {
    padding: 0.85rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.locations-table th {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
}

.locations-table tbody tr:hover {
    background: rgba(16, 185, 129, 0.03);
}

.table-thumb {
    width: 3.25rem;
    height: 2.25rem;
    object-fit: cover;
    border-radius: 0.45rem;
    border: 1px solid #e2e8f0;
}

.location-name {
    display: block;
    font-size: 0.88rem;
    color: #0f172a;
}

.location-snippet {
    margin-top: 0.2rem;
    max-width: 16rem;
    font-size: 0.76rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.action-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}

.action-btn {
    border: 1px solid #cbd5e1;
    background: #ffffff;
    border-radius: 0.45rem;
    padding: 0.35rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    color: #0f172a;
    transition:
        border-color 0.2s ease,
        color 0.2s ease,
        background 0.2s ease;
}

.action-btn.view:hover {
    border-color: #0284c7;
    color: #0284c7;
}

.action-btn.edit:hover {
    border-color: #10b981;
    color: #059669;
}

.action-btn.delete {
    color: #dc2626;
}

.action-btn.delete:hover {
    border-color: #dc2626;
    background: rgba(220, 38, 38, 0.05);
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
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
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
    margin: 0.55rem auto 1rem;
    max-width: 26rem;
    font-size: 0.9rem;
    color: #64748b;
}

@media (max-width: 720px) {
    .page-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
