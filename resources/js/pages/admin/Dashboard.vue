<script setup>
import { Link } from "@inertiajs/vue3";
import AdminLayout from "../../layouts/AdminLayout.vue";

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    topRatedLocations: {
        type: Array,
        default: () => [],
    },
});

const maxRating = 5;

const barWidth = (rating) => `${(rating / maxRating) * 100}%`;

const statCards = [
    {
        key: "locations",
        label: "Total Locations",
        valueKey: "locations_count",
        hint: "+ registered tourism spots",
        icon: "map",
    },
    {
        key: "users",
        label: "App Users",
        valueKey: "app_users_count",
        hint: "+ mobile app accounts",
        icon: "users",
    },
    {
        key: "reviews",
        label: "App Reviews",
        valueKey: "reviews_count",
        hint: "+ ratings submitted",
        icon: "star",
    },
    {
        key: "status",
        label: "System Status",
        value: "Online",
        hint: "All services operational",
        icon: "activity",
    },
];

const quickActions = [
    {
        label: "Add new location",
        description: "Register a tourism destination",
        href: "/admin/locations/create",
        icon: "plus",
    },
    {
        label: "View app reviews",
        description: "Read ratings and comments",
        href: "/admin/reviews",
        icon: "star",
    },
    {
        label: "Manage users",
        description: "Review admin and app accounts",
        href: "/admin/users",
        icon: "users",
    },
    {
        label: "System settings",
        description: "Update portal configuration",
        href: "/admin/settings",
        icon: "settings",
    },
];
</script>

<template>
    <AdminLayout
        title="Dashboard"
        description="Overview of tourism data, activity, and system status."
    >
        <div class="space-y-4 md:space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in statCards"
                    :key="card.key"
                    class="rounded-lg border bg-card text-card-foreground shadow-sm"
                >
                    <div class="flex flex-row items-center justify-between p-6 pb-2">
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ card.label }}
                        </p>
                        <div
                            class="flex size-8 items-center justify-center rounded-md bg-muted text-muted-foreground"
                        >
                            <svg
                                v-if="card.icon === 'map'"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="size-4"
                            >
                                <path
                                    d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11z"
                                />
                                <circle cx="12" cy="10" r="2.5" />
                            </svg>
                            <svg
                                v-else-if="card.icon === 'users'"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="size-4"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            <svg
                                v-else-if="card.icon === 'star'"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="size-4"
                            >
                                <path
                                    d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                />
                            </svg>
                            <svg
                                v-else
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="size-4"
                            >
                                <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                            </svg>
                        </div>
                    </div>
                    <div class="p-6 pt-0">
                        <p class="text-2xl font-bold tracking-tight">
                            {{
                                card.value ??
                                stats[card.valueKey]?.toLocaleString()
                            }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ card.hint }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-7">
                <div
                    class="rounded-lg border bg-card text-card-foreground shadow-sm lg:col-span-4"
                >
                    <div class="flex flex-col gap-1.5 border-b p-6">
                        <h2 class="text-base font-semibold leading-none">
                            Top Rated Locations
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Highest average ratings from mobile app users
                        </p>
                    </div>

                    <div class="p-6">
                        <div
                            v-if="topRatedLocations.length"
                            class="space-y-4"
                        >
                            <div
                                class="hidden grid-cols-[minmax(0,9rem)_1fr_5rem] gap-4 text-xs font-medium text-muted-foreground sm:grid"
                            >
                                <span>Location</span>
                                <span>Average rating</span>
                                <span class="text-right">Reviews</span>
                            </div>

                            <div
                                v-for="location in topRatedLocations"
                                :key="location.id"
                                class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,9rem)_1fr_5rem] sm:items-center sm:gap-4"
                            >
                                <p
                                    class="truncate text-sm font-medium"
                                    :title="location.name"
                                >
                                    {{ location.name }}
                                </p>
                                <div class="space-y-1.5">
                                    <div
                                        class="relative h-2 overflow-hidden rounded-full bg-secondary"
                                    >
                                        <div
                                            class="absolute inset-y-0 left-0 rounded-full bg-primary transition-all duration-500"
                                            :style="{
                                                width: barWidth(
                                                    location.average_rating,
                                                ),
                                            }"
                                        />
                                    </div>
                                    <div
                                        class="flex items-center justify-between text-xs text-muted-foreground sm:hidden"
                                    >
                                        <span
                                            >{{ location.average_rating }} /
                                            5</span
                                        >
                                        <span
                                            >{{ location.ratings_count }}
                                            reviews</span
                                        >
                                    </div>
                                </div>
                                <div class="hidden text-right sm:block">
                                    <p class="text-sm font-medium">
                                        {{ location.average_rating }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ location.ratings_count }}
                                        {{
                                            location.ratings_count === 1
                                                ? "review"
                                                : "reviews"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center rounded-lg border border-dashed px-6 py-10 text-center"
                        >
                            <div
                                class="mb-3 flex size-10 items-center justify-center rounded-full bg-muted"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="size-4 text-muted-foreground"
                                >
                                    <path
                                        d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                    />
                                </svg>
                            </div>
                            <p class="text-sm font-medium">
                                No rated locations yet
                            </p>
                            <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                                Ratings will appear here once app users review
                                tourism locations.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-lg border bg-card text-card-foreground shadow-sm lg:col-span-3"
                >
                    <div class="flex flex-col gap-1.5 border-b p-6">
                        <h2 class="text-base font-semibold leading-none">
                            Quick Actions
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Common admin tasks and shortcuts
                        </p>
                    </div>

                    <div class="space-y-1 p-4">
                        <Link
                            v-for="action in quickActions"
                            :key="action.href"
                            :href="action.href"
                            class="flex items-start gap-3 rounded-md p-3 text-left transition-colors hover:bg-muted"
                        >
                            <div
                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <svg
                                    v-if="action.icon === 'plus'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="size-4"
                                >
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                                <svg
                                    v-else-if="action.icon === 'star'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="size-4"
                                >
                                    <path
                                        d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"
                                    />
                                </svg>
                                <svg
                                    v-else-if="action.icon === 'users'"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="size-4"
                                >
                                    <path
                                        d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="9" cy="7" r="4" />
                                </svg>
                                <svg
                                    v-else
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="size-4"
                                >
                                    <path
                                        d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"
                                    />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium">
                                    {{ action.label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ action.description }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
