<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { toastFormErrors } from "../../../composables/useFlashToast";

const props = defineProps({
    heroSection: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    _method: "put",
    title: props.heroSection.title ?? "",
    subtitle: props.heroSection.subtitle ?? "",
    media: null,
    is_active: props.heroSection.is_active,
});

const mediaPreview = ref(null);
const mediaInput = ref(null);

const openMediaPicker = () => {
    mediaInput.value?.click();
};

const handleMediaChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.media = file;

    if (mediaPreview.value) {
        URL.revokeObjectURL(mediaPreview.value);
    }

    mediaPreview.value = file ? URL.createObjectURL(file) : null;
    event.target.value = "";
};

const submit = () => {
    form.post(`/admin/settings/hero-sections/${props.heroSection.id}`, {
        forceFormData: true,
        onError: (errors) => toastFormErrors(errors),
        onFinish: () => form.reset("media"),
    });
};
</script>

<template>
    <AdminLayout
        title="Edit Hero Section"
        description="Update the hero banner shown in the mobile app."
    >
        <div class="form-shell">
            <div class="form-header">
                <Link href="/admin/settings" class="back-link">
                    ← Back to settings
                </Link>
                <h2>Edit Hero Section</h2>
                <p>
                    Update the details below. Leave media empty to keep the
                    current image or video.
                </p>
            </div>

            <form class="hero-form" @submit.prevent="submit">
                <div class="form-layout">
                    <div class="form-fields">
                        <div class="fields-grid">
                            <div class="field">
                                <label for="title">Title</label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Welcome to Baao"
                                />
                                <p v-if="form.errors.title" class="field-error">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="status">Status</label>
                                <select id="status" v-model="form.is_active">
                                    <option :value="true">Active</option>
                                    <option :value="false">Inactive</option>
                                </select>
                                <p
                                    v-if="form.errors.is_active"
                                    class="field-error"
                                >
                                    {{ form.errors.is_active }}
                                </p>
                            </div>

                            <div class="field full">
                                <label for="subtitle">Subtitle</label>
                                <textarea
                                    id="subtitle"
                                    v-model="form.subtitle"
                                    rows="4"
                                    placeholder="Discover nature, culture, food, and resorts."
                                />
                                <p
                                    v-if="form.errors.subtitle"
                                    class="field-error"
                                >
                                    {{ form.errors.subtitle }}
                                </p>
                            </div>

                            <div class="field full">
                                <label>Replace Media (optional)</label>
                                <button
                                    type="button"
                                    class="pick-media-btn"
                                    @click="openMediaPicker"
                                >
                                    Choose Image or Video
                                </button>
                                <input
                                    ref="mediaInput"
                                    type="file"
                                    accept="image/*,video/*"
                                    class="hidden-input"
                                    @change="handleMediaChange"
                                />
                                <p v-if="form.errors.media" class="field-error">
                                    {{ form.errors.media }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <aside class="media-panel">
                        <label>Current Media</label>
                        <div class="media-preview">
                            <img
                                v-if="
                                    mediaPreview &&
                                    form.media?.type?.startsWith('image/')
                                "
                                :src="mediaPreview"
                                alt="New hero preview"
                            />
                            <video
                                v-else-if="
                                    mediaPreview &&
                                    form.media?.type?.startsWith('video/')
                                "
                                :src="mediaPreview"
                                muted
                                playsinline
                                preload="metadata"
                            />
                            <img
                                v-else-if="heroSection.type === 'image'"
                                :src="heroSection.media_url"
                                :alt="heroSection.title || 'Hero image'"
                            />
                            <video
                                v-else
                                :src="heroSection.media_url"
                                controls
                                playsinline
                                preload="metadata"
                            />
                        </div>
                        <span class="media-type">{{ heroSection.type }}</span>
                    </aside>
                </div>

                <div class="form-actions">
                    <Link href="/admin/settings" class="cancel-btn">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="submit-btn"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? "Saving changes..."
                                : "Update Hero Section"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.form-shell {
    width: 100%;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    overflow: hidden;
}

.form-header {
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(
        90deg,
        rgba(16, 185, 129, 0.08) 0%,
        rgba(2, 132, 199, 0.05) 100%
    );
}

.back-link {
    display: inline-block;
    margin-bottom: 0.55rem;
    font-size: 0.82rem;
    font-weight: 600;
    color: #0284c7;
    text-decoration: none;
}

.form-header h2 {
    font-family: "Outfit", sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}

.form-header p {
    margin-top: 0.35rem;
    font-size: 0.86rem;
    color: #64748b;
}

.hero-form {
    padding: 1.25rem;
}

.form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(16rem, 1fr);
    gap: 1.5rem;
    align-items: stretch;
}

.fields-grid {
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

.field label,
.media-panel > label {
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

.field textarea {
    resize: vertical;
    min-height: 6rem;
}

.field-error {
    margin-top: 0.35rem;
    font-size: 0.78rem;
    color: #dc2626;
}

.pick-media-btn {
    align-self: flex-start;
    border: 1px solid #10b981;
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    border-radius: 0.55rem;
    padding: 0.5rem 0.8rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.hidden-input {
    display: none;
}

.media-panel {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #f8fafc;
}

.media-preview {
    flex: 1;
    margin-top: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    overflow: hidden;
    min-height: 16rem;
    background: #0f172a;
}

.media-preview img,
.media-preview video {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 16rem;
    object-fit: cover;
}

.media-type {
    margin-top: 0.65rem;
    align-self: flex-start;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.65rem;
    margin-top: 1.25rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.cancel-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.65rem 0.95rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
}

.submit-btn {
    border: none;
    border-radius: 0.6rem;
    padding: 0.65rem 1rem;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: #ffffff;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
}

.submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

@media (max-width: 960px) {
    .form-layout {
        grid-template-columns: 1fr;
    }

    .fields-grid {
        grid-template-columns: 1fr;
    }
}
</style>
