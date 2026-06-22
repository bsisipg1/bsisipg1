<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { toastFormErrors } from "../../../composables/useFlashToast";

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => [],
    },
    tones: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    title: props.event.title,
    type: props.event.type,
    description: props.event.description,
    event_date: props.event.event_date,
    time: props.event.time ?? "",
    venue: props.event.venue,
    tone: props.event.tone,
    image: null,
    is_active: props.event.is_active,
});

const imagePreview = ref(props.event.image_url);

const handleImageChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.image = file;

    if (imagePreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(imagePreview.value);
    }

    imagePreview.value = file
        ? URL.createObjectURL(file)
        : props.event.image_url;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: "put",
    })).post(`/admin/events/${props.event.id}`, {
        forceFormData: true,
        onError: (errors) => toastFormErrors(errors),
    });
};
</script>

<template>
    <AdminLayout
        title="Edit Event"
        description="Update event details shown in the mobile app."
    >
        <div class="form-shell">
            <div class="form-header">
                <Link href="/admin/events" class="back-link">
                    ← Back to events
                </Link>
                <h2>Edit Event Record</h2>
                <p>
                    Update official festivals, cultural programs, and community
                    gatherings for the mobile app.
                </p>
            </div>

            <form class="event-form" @submit.prevent="submit">
                <div class="form-layout">
                    <div class="form-fields">
                        <div class="fields-grid">
                            <div class="field full">
                                <label for="title">Event Title</label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="e.g. Baao Town Fiesta"
                                />
                                <p v-if="form.errors.title" class="field-error">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="type">Event Type</label>
                                <select id="type" v-model="form.type">
                                    <option
                                        v-for="type in types"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.type" class="field-error">
                                    {{ form.errors.type }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="tone">Card Tone</label>
                                <select id="tone" v-model="form.tone">
                                    <option
                                        v-for="tone in tones"
                                        :key="tone.value"
                                        :value="tone.value"
                                    >
                                        {{ tone.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.tone" class="field-error">
                                    {{ form.errors.tone }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="event_date">Event Date</label>
                                <input
                                    id="event_date"
                                    v-model="form.event_date"
                                    type="date"
                                />
                                <p
                                    v-if="form.errors.event_date"
                                    class="field-error"
                                >
                                    {{ form.errors.event_date }}
                                </p>
                            </div>

                            <div class="field">
                                <label for="time">Time</label>
                                <input
                                    id="time"
                                    v-model="form.time"
                                    type="text"
                                    placeholder="All Day or 6:00 PM - 10:00 PM"
                                />
                                <p v-if="form.errors.time" class="field-error">
                                    {{ form.errors.time }}
                                </p>
                            </div>

                            <div class="field full">
                                <label for="venue">Venue</label>
                                <input
                                    id="venue"
                                    v-model="form.venue"
                                    type="text"
                                    placeholder="Poblacion, Baao"
                                />
                                <p v-if="form.errors.venue" class="field-error">
                                    {{ form.errors.venue }}
                                </p>
                            </div>

                            <div class="field full">
                                <label for="description">Description</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="6"
                                    placeholder="Describe the event, activities, and what visitors can expect."
                                />
                                <p
                                    v-if="form.errors.description"
                                    class="field-error"
                                >
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <div class="field full">
                                <label class="publish-label">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                    />
                                    Publish to mobile app
                                </label>
                            </div>
                        </div>
                    </div>

                    <aside class="image-panel">
                        <label for="image">Cover Image</label>
                        <input
                            id="image"
                            type="file"
                            accept="image/*"
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
                                alt="Event preview"
                            />
                            <div v-else class="image-placeholder">
                                <span>+</span>
                                <p>Upload an optional event cover image</p>
                            </div>
                        </div>
                    </aside>
                </div>

                <div class="form-actions">
                    <Link href="/admin/events" class="cancel-btn">
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
                                : "Save Changes"
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
    background: #f8fafc;
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

.event-form {
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

.publish-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    font-size: 0.86rem;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
}

.publish-label input {
    width: auto;
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
    opacity: 0.7;
    cursor: not-allowed;
}

@media (max-width: 900px) {
    .form-layout {
        grid-template-columns: 1fr;
    }

    .fields-grid {
        grid-template-columns: 1fr;
    }
}
</style>
