<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post("/admin/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-teal-600">
                    Baao Tourism
                </p>
                <h1 class="mt-2 text-3xl font-bold text-slate-900">
                    Admin Login
                </h1>
                <p class="mt-2 text-sm text-slate-600">
                    Sign in to manage the tourism platform.
                </p>
            </div>

            <form
                class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="space-y-5">
                    <div>
                        <label
                            for="email"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1.5 text-sm text-red-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20"
                        />
                        <p
                            v-if="form.errors.password"
                            class="mt-1.5 text-sm text-red-600"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                        />
                        Remember me
                    </label>
                </div>

                <button
                    type="submit"
                    class="mt-6 w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Signing in..." : "Sign in" }}
                </button>
            </form>
        </div>
    </div>
</template>
