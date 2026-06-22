<script setup>
import { Link, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import AdminLayout from "../../../layouts/AdminLayout.vue";
import { useFlashToast } from "../../../composables/useFlashToast";

defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});

useFlashToast();

const deleteEvent = (event) => {
    if (!confirm(`Delete "${event.title}"? This action cannot be undone.`)) {
        return;
    }

    router.delete(`/admin/events/${event.id}`, {
        onError: () => {
            toast.error("Unable to delete event. Please try again.");
        },
    });
};
</script>

<template>
    <AdminLayout
        title="Events"
        description="Manage upcoming festivals, cultural events, and community gatherings."
    >
        <div
            class="mb-4 flex flex-col gap-3 rounded-xl border border-tourism/10 bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2
                    class="font-[family-name:var(--font-display)] text-base font-bold text-foreground"
                >
                    Tourism Events
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ events.length }} event(s) on record
                </p>
            </div>
            <Link
                href="/admin/events/create"
                class="inline-flex items-center justify-center rounded-md bg-gradient-to-r from-tourism-dark to-tourism px-4 py-2 text-sm font-semibold text-white shadow-sm"
            >
                + Create Event
            </Link>
        </div>

        <section
            v-if="events.length"
            class="overflow-hidden rounded-xl border border-tourism/10 bg-card shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="min-w-[56rem] w-full border-collapse">
                    <thead>
                        <tr class="border-b bg-muted/50 text-left">
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Event
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Type
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Date
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Venue
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Status
                            </th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="event in events"
                            :key="event.id"
                            class="border-b last:border-b-0 hover:bg-tourism/5"
                        >
                            <td class="px-4 py-3">
                                <strong class="block text-sm text-foreground">{{
                                    event.title
                                }}</strong>
                                <p class="mt-1 max-w-xs truncate text-xs text-muted-foreground">
                                    {{ event.description }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full border border-tourism/20 bg-tourism/10 px-2.5 py-0.5 text-xs font-semibold text-tourism-dark"
                                >
                                    {{ event.type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-foreground">
                                <p>{{ event.date }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ event.time || "—" }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">
                                {{ event.venue }}
                            </td>
                            <td class="px-4 py-3">
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
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Link
                                        :href="`/admin/events/${event.id}`"
                                        class="rounded-md border px-2.5 py-1 text-xs font-semibold text-foreground hover:border-tourism-blue hover:text-tourism-blue"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="`/admin/events/${event.id}/edit`"
                                        class="rounded-md border px-2.5 py-1 text-xs font-semibold text-foreground hover:border-tourism hover:text-tourism-dark"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="rounded-md border px-2.5 py-1 text-xs font-semibold text-destructive hover:bg-destructive/5"
                                        @click="deleteEvent(event)"
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

        <section
            v-else
            class="rounded-xl border border-dashed border-tourism/20 bg-card px-6 py-12 text-center shadow-sm"
        >
            <div
                class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-tourism/10 text-tourism-dark"
            >
                ★
            </div>
            <h3 class="text-base font-bold text-foreground">No events yet</h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Create the first official tourism event for the mobile app and
                public listings.
            </p>
            <Link
                href="/admin/events/create"
                class="mt-4 inline-flex items-center justify-center rounded-md bg-gradient-to-r from-tourism-dark to-tourism px-4 py-2 text-sm font-semibold text-white"
            >
                + Create Event
            </Link>
        </section>
    </AdminLayout>
</template>
