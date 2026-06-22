<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { toastFormErrors } from "../../../composables/useFlashToast";

defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: "",
    category: "",
    description: "",
    latitude: "",
    longitude: "",
    image: null,
    gallery: [],
});

const geoStatus = ref("Detecting your current location...");
const imagePreview = ref(null);
const galleryInput = ref(null);
const galleryItems = ref([]);

const detectLocation = () => {
    if (!navigator.geolocation) {
        geoStatus.value =
            "Geolocation is not supported by this browser. Please enter coordinates manually.";
        return;
    }

    geoStatus.value = "Detecting your current location...";

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude.toFixed(7);
            form.longitude = position.coords.longitude.toFixed(7);
            geoStatus.value = "Location detected from this device.";
        },
        () => {
            geoStatus.value =
                "Unable to detect location. Please allow location access or enter coordinates manually.";
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        },
    );
};

const handleImageChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.image = file;

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }

    imagePreview.value = file ? URL.createObjectURL(file) : null;
};

const syncGalleryForm = () => {
    form.gallery = galleryItems.value.map((item) => item.file);
};

const openGalleryPicker = () => {
    galleryInput.value?.click();
};

const handleGalleryChange = (event) => {
    const files = Array.from(event.target.files ?? []);

    files.forEach((file) => {
        galleryItems.value.push({
            id: crypto.randomUUID(),
            file,
            preview: URL.createObjectURL(file),
            type: file.type.startsWith("video/") ? "video" : "image",
        });
    });

    syncGalleryForm();
    event.target.value = "";
};

const removeGalleryItem = (id) => {
    const index = galleryItems.value.findIndex((item) => item.id === id);

    if (index === -1) {
        return;
    }

    URL.revokeObjectURL(galleryItems.value[index].preview);
    galleryItems.value.splice(index, 1);
    syncGalleryForm();
};

const submit = () => {
    syncGalleryForm();

    form.post("/admin/locations", {
        forceFormData: true,
        onError: (errors) => toastFormErrors(errors),
        onFinish: () => form.reset("image", "gallery"),
    });
};

onMounted(() => {
    detectLocation();
});
</script>

<template>
    <AdminLayout
        title="Create Location"
        description="Register a new official tourism location with map coordinates and image."
    >
        <div class="form-shell">
            <div class="form-header">
                <Link href="/admin/locations" class="back-link">
                    ← Back to locations
                </Link>
                <h2>New Location Record</h2>
                <p>
                    Coordinates are filled automatically from your device
                    location. You can adjust them if needed.
                </p>
            </div>

            <form class="location-form" @submit.prevent="submit">
                <div class="form-layout">
                    <div class="form-fields">
                        <div class="fields-grid">
                            <div class="field full">
                                <label for="name">Location Name</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="e.g. Baao Lake View Park"
                                />
                                <p v-if="form.errors.name" class="field-error">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="category">Category</label>
                                <select
                                    id="category"
                                    v-model="form.category"
                                    required
                                >
                                    <option value="" disabled>
                                        Select category
                                    </option>
                                    <option
                                        v-for="category in categories"
                                        :key="category.value"
                                        :value="category.value"
                                    >
                                        {{ category.label }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.category"
                                    class="field-error"
                                >
                                    {{ form.errors.category }}
                                </p>
                            </div>

                            <div class="field">
                                <label>Coordinates</label>
                                <div
                                    class="geo-banner"
                                    :class="{
                                        error:
                                            geoStatus.includes('Unable') ||
                                            geoStatus.includes('not supported'),
                                    }"
                                >
                                    {{ geoStatus }}
                                </div>
                                <button
                                    type="button"
                                    class="refresh-geo-btn"
                                    @click="detectLocation"
                                >
                                    Refresh device location
                                </button>
                            </div>

                            <div class="field">
                                <label for="latitude">Latitude</label>
                                <input
                                    id="latitude"
                                    v-model="form.latitude"
                                    type="number"
                                    step="any"
                                    required
                                    placeholder="13.4481000"
                                />
                                <p
                                    v-if="form.errors.latitude"
                                    class="field-error"
                                >
                                    {{ form.errors.latitude }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="longitude">Longitude</label>
                                <input
                                    id="longitude"
                                    v-model="form.longitude"
                                    type="number"
                                    step="any"
                                    required
                                    placeholder="123.3562000"
                                />
                                <p
                                    v-if="form.errors.longitude"
                                    class="field-error"
                                >
                                    {{ form.errors.longitude }}
                                </p>
                            </div>

                            <div class="field full">
                                <label for="description">Description</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="6"
                                    required
                                    placeholder="Describe the location, access details, and tourism highlights."
                                />
                                <p
                                    v-if="form.errors.description"
                                    class="field-error"
                                >
                                    {{ form.errors.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <aside class="image-panel">
                        <label for="image">Location Image</label>
                        <input
                            id="image"
                            type="file"
                            accept="image/*"
                            required
                            class="image-input"
                            @change="handleImageChange"
                        />
                        <p v-if="form.errors.image" class="field-error">
                            {{ form.errors.image }}
                        </p>

                        <div
                            class="image-preview"
                            :class="{ empty: !imagePreview }"
                        >
                            <img
                                v-if="imagePreview"
                                :src="imagePreview"
                                alt="Selected location preview"
                            />
                            <div v-else class="image-placeholder">
                                <span>⌖</span>
                                <p>Upload a photo for this location</p>
                            </div>
                        </div>
                    </aside>
                </div>

                <section class="gallery-section">
                    <div class="gallery-header">
                        <div>
                            <h3>Location Gallery</h3>
                            <p>
                                Add multiple photos or videos to showcase this
                                location in the app.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="add-gallery-btn"
                            @click="openGalleryPicker"
                        >
                            + Add Gallery
                        </button>
                    </div>

                    <input
                        ref="galleryInput"
                        type="file"
                        accept="image/*,video/*"
                        multiple
                        class="gallery-input"
                        @change="handleGalleryChange"
                    />

                    <p v-if="form.errors.gallery" class="field-error">
                        {{ form.errors.gallery }}
                    </p>
                    <p
                        v-for="(error, index) in form.errors"
                        :key="`gallery-error-${index}`"
                        v-show="String(index).startsWith('gallery.')"
                        class="field-error"
                    >
                        {{ error }}
                    </p>

                    <div v-if="galleryItems.length" class="gallery-grid">
                        <article
                            v-for="item in galleryItems"
                            :key="item.id"
                            class="gallery-card"
                        >
                            <img
                                v-if="item.type === 'image'"
                                :src="item.preview"
                                alt="Gallery preview"
                            />
                            <video
                                v-else
                                :src="item.preview"
                                muted
                                playsinline
                                preload="metadata"
                            />

                            <span class="gallery-type">{{ item.type }}</span>

                            <button
                                type="button"
                                class="gallery-remove-btn"
                                @click="removeGalleryItem(item.id)"
                            >
                                Remove
                            </button>
                        </article>
                    </div>

                    <div v-else class="gallery-empty">
                        <p>No gallery items yet. Click “Add Gallery” to upload.</p>
                    </div>
                </section>

                <div class="form-actions">
                    <Link href="/admin/locations" class="cancel-btn">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="submit-btn"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? "Saving location..."
                                : "Save Location"
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

.back-link:hover {
    color: #0369a1;
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

.location-form {
    padding: 1.25rem;
}

.form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(16rem, 1fr);
    gap: 1.5rem;
    align-items: stretch;
}

.form-fields {
    min-width: 0;
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
.image-panel > label {
    margin-bottom: 0.4rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0f172a;
}

.field input,
.field select,
.field textarea,
.image-input {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    padding: 0.68rem 0.8rem;
    font-size: 0.9rem;
    color: #0f172a;
    background: #ffffff;
    outline: none;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
}

.field textarea {
    resize: vertical;
    min-height: 8rem;
}

.field-error {
    margin-top: 0.35rem;
    font-size: 0.78rem;
    color: #dc2626;
}

.geo-banner {
    padding: 0.7rem 0.8rem;
    border-radius: 0.6rem;
    border: 1px solid rgba(16, 185, 129, 0.2);
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    font-size: 0.82rem;
    font-weight: 600;
}

.geo-banner.error {
    border-color: rgba(220, 38, 38, 0.2);
    background: rgba(220, 38, 38, 0.06);
    color: #b91c1c;
}

.refresh-geo-btn {
    margin-top: 0.55rem;
    align-self: flex-start;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #0f172a;
    border-radius: 0.55rem;
    padding: 0.45rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.refresh-geo-btn:hover {
    border-color: #10b981;
    color: #059669;
}

.image-panel {
    display: flex;
    flex-direction: column;
    min-height: 100%;
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #f8fafc;
}

.image-preview {
    flex: 1;
    margin-top: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    overflow: hidden;
    min-height: 16rem;
    background: #ffffff;
}

.image-preview.empty {
    border-style: dashed;
    background: #ffffff;
}

.image-preview img {
    display: block;
    width: 100%;
    height: 100%;
    min-height: 16rem;
    object-fit: cover;
}

.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 16rem;
    padding: 1.5rem;
    text-align: center;
    color: #94a3b8;
}

.image-placeholder span {
    width: 3rem;
    height: 3rem;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    font-size: 1.35rem;
    font-weight: 700;
}

.image-placeholder p {
    font-size: 0.85rem;
    font-weight: 600;
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

.gallery-section {
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e2e8f0;
}

.gallery-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.gallery-header h3 {
    font-family: "Outfit", sans-serif;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}

.gallery-header p {
    margin-top: 0.35rem;
    font-size: 0.84rem;
    color: #64748b;
}

.add-gallery-btn {
    border: 1px solid #10b981;
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    border-radius: 0.6rem;
    padding: 0.55rem 0.9rem;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}

.add-gallery-btn:hover {
    background: rgba(16, 185, 129, 0.14);
}

.gallery-input {
    display: none;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr));
    gap: 0.85rem;
}

.gallery-card {
    position: relative;
    border: 1px solid #e2e8f0;
    border-radius: 0.65rem;
    overflow: hidden;
    background: #f8fafc;
    min-height: 9rem;
}

.gallery-card img,
.gallery-card video {
    display: block;
    width: 100%;
    height: 9rem;
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

.gallery-remove-btn {
    position: absolute;
    right: 0.45rem;
    bottom: 0.45rem;
    border: none;
    border-radius: 0.45rem;
    padding: 0.28rem 0.5rem;
    background: rgba(220, 38, 38, 0.92);
    color: #ffffff;
    font-size: 0.68rem;
    font-weight: 700;
    cursor: pointer;
}

.gallery-empty {
    padding: 1.25rem;
    border: 1px dashed #cbd5e1;
    border-radius: 0.65rem;
    text-align: center;
    color: #94a3b8;
    font-size: 0.84rem;
    font-weight: 600;
}

@media (max-width: 960px) {
    .form-layout {
        grid-template-columns: 1fr;
    }

    .image-panel {
        min-height: auto;
    }
}

@media (max-width: 640px) {
    .fields-grid {
        grid-template-columns: 1fr;
    }
}
</style>
