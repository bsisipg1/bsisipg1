<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { toast } from "vue-sonner";
import AdminLayout from "../../layouts/AdminLayout.vue";
import {
    toastFormErrors,
    useFlashToast,
} from "../../composables/useFlashToast";

const props = defineProps({
    appDownloadUrl: {
        type: String,
        default: "",
    },
    heroSections: {
        type: Array,
        default: () => [],
    },
});

useFlashToast();

const appSettingsForm = useForm({
    app_download_url: props.appDownloadUrl ?? "",
});

const submitAppSettings = () => {
    appSettingsForm.put("/admin/settings/app", {
        preserveScroll: true,
        onError: (errors) => toastFormErrors(errors),
    });
};

const mediaPreview = ref(null);
const mediaInput = ref(null);

const createForm = useForm({
    title: "",
    subtitle: "",
    media: null,
    is_active: true,
});

const resetMediaPreview = () => {
    if (mediaPreview.value) {
        URL.revokeObjectURL(mediaPreview.value);
        mediaPreview.value = null;
    }
};

const openMediaPicker = () => {
    mediaInput.value?.click();
};

const handleMediaChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    createForm.media = file;
    resetMediaPreview();
    mediaPreview.value = file ? URL.createObjectURL(file) : null;
    event.target.value = "";
};

const submitCreate = () => {
    createForm.post("/admin/settings/hero-sections", {
        forceFormData: true,
        onSuccess: () => {
            createForm.reset();
            createForm.is_active = true;
            resetMediaPreview();
        },
        onError: (errors) => toastFormErrors(errors),
    });
};

const deleteSection = (section) => {
    const label = section.title || `Hero #${section.id}`;

    if (!confirm(`Remove "${label}" from the app hero?`)) {
        return;
    }

    router.delete(`/admin/settings/hero-sections/${section.id}`, {
        onError: () => {
            toast.error("Unable to remove hero section. Please try again.");
        },
    });
};
</script>

<template>
    <AdminLayout
        title="Settings"
        description="Configure app content shown to mobile users and the public website download link."
    >
        <section class="settings-shell app-settings-shell">
            <header class="section-header">
                <div>
                    <h2>App Download Link</h2>
                    <p>
                        Set the URL used by the Download button on the public
                        home page. Use a direct link to your APK, App Store, or
                        Play Store listing.
                    </p>
                </div>
            </header>

            <form class="app-settings-form" @submit.prevent="submitAppSettings">
                <div class="field full">
                    <label for="app-download-url">Download URL</label>
                    <input
                        id="app-download-url"
                        v-model="appSettingsForm.app_download_url"
                        type="url"
                        placeholder="https://example.com/i-baao.apk"
                    />
                    <p
                        v-if="appSettingsForm.errors.app_download_url"
                        class="field-error"
                    >
                        {{ appSettingsForm.errors.app_download_url }}
                    </p>
                </div>

                <div class="form-actions">
                    <button
                        type="submit"
                        class="submit-btn"
                        :disabled="appSettingsForm.processing"
                    >
                        {{
                            appSettingsForm.processing
                                ? "Saving..."
                                : "Save Download Link"
                        }}
                    </button>
                </div>
            </form>
        </section>

        <section class="settings-shell">
            <header class="section-header">
                <div>
                    <h2>App Hero Section</h2>
                    <p>
                        Upload images or videos displayed at the top of the
                        mobile app. Set each item to Active to show it in the
                        app, or Inactive to hide it.
                    </p>
                </div>
                <span class="count-badge"
                    >{{ heroSections.length }} item(s)</span
                >
            </header>

            <section v-if="heroSections.length" class="table-shell">
                <div class="table-scroll">
                    <table class="hero-table">
                        <thead>
                            <tr>
                                <th>Media</th>
                                <th>Title</th>
                                <th>Subtitle</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="section in heroSections"
                                :key="section.id"
                                :class="{ inactive: !section.is_active }"
                            >
                                <td>
                                    <div class="table-media">
                                        <img
                                            v-if="section.type === 'image'"
                                            :src="section.media_url"
                                            :alt="section.title || 'Hero image'"
                                            class="table-thumb"
                                        />
                                        <video
                                            v-else
                                            :src="section.media_url"
                                            class="table-thumb video-thumb"
                                            muted
                                            playsinline
                                            preload="metadata"
                                        />
                                    </div>
                                </td>
                                <td>
                                    <strong class="hero-title">{{
                                        section.title || "Untitled hero"
                                    }}</strong>
                                </td>
                                <td>
                                    <p class="hero-snippet">
                                        {{ section.subtitle || "—" }}
                                    </p>
                                </td>
                                <td>
                                    <span class="type-pill">{{
                                        section.type
                                    }}</span>
                                </td>
                                <td>
                                    <span
                                        class="status-pill"
                                        :class="{ active: section.is_active }"
                                    >
                                        {{
                                            section.is_active
                                                ? "Active"
                                                : "Inactive"
                                        }}
                                    </span>
                                </td>
                                <td>{{ section.updated_at }}</td>
                                <td>
                                    <div class="action-group">
                                        <Link
                                            :href="`/admin/settings/hero-sections/${section.id}/edit`"
                                            class="action-btn edit"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            class="action-btn delete"
                                            @click="deleteSection(section)"
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

            <div v-else class="empty-state">
                <p>No hero content yet. Add your first image or video below.</p>
            </div>

            <input
                ref="mediaInput"
                type="file"
                accept="image/*,video/*"
                class="hidden-input"
                @change="handleMediaChange"
            />

            <form class="hero-form" @submit.prevent="submitCreate">
                <h3>Add Hero Section</h3>

                <div class="form-grid">
                    <div class="field">
                        <label for="create-title">Title</label>
                        <input
                            id="create-title"
                            v-model="createForm.title"
                            type="text"
                            placeholder="Welcome to Baao"
                        />
                        <p v-if="createForm.errors.title" class="field-error">
                            {{ createForm.errors.title }}
                        </p>
                    </div>

                    <div class="field">
                        <label for="create-status">Status</label>
                        <select id="create-status" v-model="createForm.is_active">
                            <option :value="true">Active</option>
                            <option :value="false">Inactive</option>
                        </select>
                        <p v-if="createForm.errors.is_active" class="field-error">
                            {{ createForm.errors.is_active }}
                        </p>
                    </div>

                    <div class="field full">
                        <label for="create-subtitle">Subtitle</label>
                        <textarea
                            id="create-subtitle"
                            v-model="createForm.subtitle"
                            rows="3"
                            placeholder="Discover nature, culture, food, and resorts."
                        />
                        <p
                            v-if="createForm.errors.subtitle"
                            class="field-error"
                        >
                            {{ createForm.errors.subtitle }}
                        </p>
                    </div>

                    <div class="field full">
                        <label>Hero Media</label>
                        <button
                            type="button"
                            class="pick-media-btn"
                            @click="openMediaPicker"
                        >
                            + Add Image or Video
                        </button>
                        <p v-if="createForm.errors.media" class="field-error">
                            {{ createForm.errors.media }}
                        </p>
                        <div v-if="mediaPreview && createForm.media" class="media-preview">
                            <img
                                v-if="createForm.media?.type?.startsWith('image/')"
                                :src="mediaPreview"
                                alt="Hero preview"
                            />
                            <video
                                v-else-if="createForm.media?.type?.startsWith('video/')"
                                :src="mediaPreview"
                                muted
                                playsinline
                                preload="metadata"
                            />
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button
                        type="submit"
                        class="submit-btn"
                        :disabled="createForm.processing"
                    >
                        {{
                            createForm.processing
                                ? "Adding..."
                                : "Add Hero Section"
                        }}
                    </button>
                </div>
            </form>
        </section>
    </AdminLayout>
</template>

<style scoped>
.settings-shell {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    overflow: hidden;
}

.app-settings-shell {
    margin-bottom: 1.25rem;
}

.app-settings-form {
    padding: 1.25rem;
}

.section-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(
        90deg,
        rgba(16, 185, 129, 0.08) 0%,
        rgba(2, 132, 199, 0.05) 100%
    );
}

.section-header h2 {
    font-family: "Outfit", sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}

.section-header p {
    margin-top: 0.35rem;
    max-width: 42rem;
    font-size: 0.86rem;
    color: #64748b;
    line-height: 1.55;
}

.count-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}

.table-shell {
    border-bottom: 1px solid #e2e8f0;
}

.table-scroll {
    overflow-x: auto;
}

.hero-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 52rem;
}

.hero-table th,
.hero-table td {
    padding: 0.85rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.hero-table th {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
}

.hero-table tbody tr:hover {
    background: rgba(16, 185, 129, 0.03);
}

.hero-table tbody tr.inactive {
    opacity: 0.72;
}

.table-media {
    display: flex;
    align-items: center;
}

.table-thumb {
    width: 4.5rem;
    height: 2.75rem;
    object-fit: cover;
    border-radius: 0.45rem;
    border: 1px solid #e2e8f0;
    background: #0f172a;
}

.video-thumb {
    display: block;
}

.hero-title {
    display: block;
    font-size: 0.88rem;
    color: #0f172a;
}

.hero-snippet {
    max-width: 14rem;
    font-size: 0.76rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.type-pill,
.status-pill {
    display: inline-block;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
}

.type-pill {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
}

.status-pill {
    background: rgba(148, 163, 184, 0.18);
    color: #64748b;
}

.status-pill.active {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
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
    color: #0f172a;
    text-decoration: none;
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
    padding: 1.5rem 1.25rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.88rem;
    font-weight: 600;
    border-bottom: 1px solid #e2e8f0;
}

.hero-form {
    padding: 1.25rem;
}

.hero-form h3 {
    margin-bottom: 1rem;
    font-family: "Outfit", sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.field {
    display: flex;
    flex-direction: column;
}

.field.full {
    grid-column: 1 / -1;
}

.field label {
    margin-bottom: 0.4rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0f172a;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    padding: 0.68rem 0.8rem;
    font-size: 0.9rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
}

.field-error {
    margin-top: 0.35rem;
    font-size: 0.78rem;
    color: #dc2626;
}

.pick-media-btn,
.submit-btn {
    border-radius: 0.55rem;
    padding: 0.5rem 0.8rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.pick-media-btn {
    align-self: flex-start;
    border: 1px solid #10b981;
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
}

.hidden-input {
    display: none;
}

.media-preview {
    margin-top: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    overflow: hidden;
}

.media-preview img,
.media-preview video {
    display: block;
    width: 100%;
    max-height: 14rem;
    object-fit: cover;
    background: #0f172a;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.65rem;
    margin-top: 1rem;
}

.submit-btn {
    border: none;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: #ffffff;
}

.submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

@media (max-width: 720px) {
    .section-header {
        flex-direction: column;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
