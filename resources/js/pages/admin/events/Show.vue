<script setup>
import { Link, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { useFlashToast } from "../../../composables/useFlashToast";

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

useFlashToast();

const deleteEvent = () => {
    if (
        !confirm(
            `Delete "${props.event.title}"? This action cannot be undone.`,
        )
    ) {
        return;
    }

    router.delete(`/admin/events/${props.event.id}`, {
        onError: () => {
            toast.error("Unable to delete event. Please try again.");
        },
    });
};
</script>

<template>
    <AdminLayout
        title="View Event"
        description="Official record details for this tourism event."
    >
        <div class="mx-auto max-w-4xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <Link
                    href="/admin/events"
                    class="text-sm font-semibold text-tourism-dark hover:underline"
                >
                    ← Back to events
                </Link>
                <div class="flex gap-2">
                    <Link
                        :href="`/admin/events/${event.id}/edit`"
                        class="rounded-md border px-3 py-1.5 text-sm font-semibold hover:border-tourism hover:text-tourism-dark"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm font-semibold text-destructive hover:bg-destructive/5"
                        @click="deleteEvent"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-tourism/10 bg-card shadow-sm"
            >
                <img
                    v-if="event.image_url"
                    :src="event.image_url"
                    :alt="event.title"
                    class="h-56 w-full object-cover"
                />

                <div class="space-y-4 p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex rounded-full border border-tourism/20 bg-tourism/10 px-2.5 py-0.5 text-xs font-semibold text-tourism-dark"
                        >
                            {{ event.type_label }}
                        </span>
                        <span
                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="
                                event.is_active
                                    ? 'bg-tourism/10 text-tourism-dark'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{
                                event.is_active ? "Published" : "Draft"
                            }}
                        </span>
                    </div>

                    <h2
                        class="font-[family-name:var(--font-display)] text-2xl font-bold text-foreground"
                    >
                        {{ event.title }}
                    </h2>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ event.description }}
                    </p>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Date
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-foreground">
                                {{ event.date }}
                            </dd>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Time
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-foreground">
                                {{ event.time || "—" }}
                            </dd>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-4 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Venue
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-foreground">
                                {{ event.venue }}
                            </dd>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Card Tone
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-foreground">
                                {{ event.tone }}
                            </dd>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Created
                            </dt>
                            <dd class="mt-1 text-sm font-semibold text-foreground">
                                {{ event.created_at }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
