<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebarOpen = ref(false);

const logoutForm = useForm({});

const navItems = [
    {
        label: "Dashboard",
        href: "/admin/dashboard",
        match: "/admin/dashboard",
        icon: "dashboard",
    },
    {
        label: "Locations",
        href: "/admin/locations",
        match: "/admin/locations",
        icon: "locations",
    },
    {
        label: "Events",
        href: "/admin/events",
        match: "/admin/events",
        icon: "events",
    },
    {
        label: "Users",
        href: "/admin/users",
        match: "/admin/users",
        icon: "users",
    },
    {
        label: "Reviews",
        href: "/admin/reviews",
        match: "/admin/reviews",
        icon: "reviews",
    },
    {
        label: "Settings",
        href: "/admin/settings",
        match: "/admin/settings",
        icon: "settings",
    },
];

const isActive = (match) => page.url.startsWith(match);

const logout = () => {
    logoutForm.post("/admin/logout");
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-muted">
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-emerald-950/40 md:hidden"
            @click="closeSidebar"
        />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex h-screen w-64 shrink-0 flex-col overflow-hidden border-r-2 border-tourism-dark bg-gradient-to-br from-tourism-dark to-tourism text-white shadow-[2px_0_18px_rgba(16,185,129,0.22)] transition-transform duration-200 md:static md:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div
                class="flex h-16 shrink-0 items-center border-b border-white/15 bg-black/10 px-4"
            >
                <img
                    src="/assets/images/applogo.png"
                    alt="Baao Tourism logo"
                    class="size-9 rounded-full border-2 border-white/80 object-cover shadow-sm"
                />
                <div class="ml-3 min-w-0">
                    <p
                        class="truncate font-[family-name:var(--font-display)] text-sm font-bold text-white"
                    >
                        Municipality of Baao
                    </p>
                    <p
                        class="truncate text-[0.65rem] font-bold uppercase tracking-[0.05em] text-emerald-100/90"
                    >
                        Tourism Management
                    </p>
                </div>
            </div>

            <nav
                class="min-h-0 flex-1 space-y-1 overflow-hidden p-3"
                aria-label="Admin navigation"
            >
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-all"
                    :class="
                        isActive(item.match)
                            ? 'bg-white text-tourism-dark shadow-sm'
                            : 'text-white/90 hover:bg-white/15 hover:text-white'
                    "
                    @click="closeSidebar"
                >
                    <span class="size-4 shrink-0" aria-hidden="true">
                        <svg
                            v-if="item.icon === 'dashboard'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="size-4"
                        >
                            <rect x="3" y="3" width="7" height="7" rx="1" />
                            <rect x="14" y="3" width="7" height="7" rx="1" />
                            <rect x="3" y="14" width="7" height="7" rx="1" />
                            <rect x="14" y="14" width="7" height="7" rx="1" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'locations'"
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
                            v-else-if="item.icon === 'events'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="size-4"
                        >
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'users'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="size-4"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'reviews'"
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
                            v-else-if="item.icon === 'settings'"
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
                    </span>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="shrink-0 border-t border-white/15 p-3">
                <Link
                    href="/"
                    class="flex w-full items-center justify-center rounded-lg border border-white/25 bg-gradient-to-r from-sky-600/20 to-tourism/20 px-3 py-2.5 text-xs font-semibold text-white/95 transition-colors hover:from-sky-600/30 hover:to-tourism/30 hover:text-white"
                    @click="closeSidebar"
                >
                    View public website
                </Link>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <header
                class="z-30 flex h-14 shrink-0 items-center gap-4 border-b bg-background px-4 md:px-6"
            >
                <button
                    type="button"
                    class="inline-flex size-9 items-center justify-center rounded-md border border-tourism/20 bg-background text-tourism-dark shadow-sm md:hidden"
                    aria-label="Toggle navigation menu"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="size-4"
                    >
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="min-w-0 flex-1">
                    <h1
                        class="truncate font-[family-name:var(--font-display)] text-lg font-bold tracking-tight text-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="hidden items-center gap-2 sm:flex">
                        <div
                            class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-tourism-dark to-tourism text-xs font-bold text-white shadow-[0_4px_12px_rgba(16,185,129,0.25)]"
                        >
                            {{ user?.name?.charAt(0) ?? "A" }}
                        </div>
                        <div class="hidden text-left lg:block">
                            <p class="text-sm font-medium leading-none">
                                {{ user?.name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ user?.email }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-tourism/20 bg-background px-3 text-sm font-semibold text-tourism-dark shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
                        :disabled="logoutForm.processing"
                        @click="logout"
                    >
                        Logout
                    </button>
                </div>
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto p-4 md:p-6">
                <slot />
            </main>

            <footer
                class="flex shrink-0 flex-col gap-1 border-t bg-background px-4 py-4 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between md:px-6"
            >
                <p>© 2026 Municipality of Baao · Tourism Mapping System</p>
                <p>Republic of the Philippines</p>
            </footer>
        </div>
    </div>
</template>
