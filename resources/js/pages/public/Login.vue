<script setup>
import { Link, useForm } from "@inertiajs/vue3";

const appLogo = "/assets/images/applogo.png";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <div class="login-page">
        <div class="login-shell">
            <Link href="/" class="back-link">← Back to home</Link>

            <div class="login-card">
                <div class="login-header">
                    <img
                        class="login-logo"
                        :src="appLogo"
                        alt="i-Baao logo"
                    />
                    <p class="login-eyebrow">Baao Tourism</p>
                    <h1>Sign in</h1>
                    <p class="login-subtitle">
                        Access the tourism management dashboard.
                    </p>
                </div>

                <form class="login-form" @submit.prevent="submit">
                    <div class="field">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            required
                            placeholder="you@example.com"
                        />
                        <p v-if="form.errors.email" class="field-error">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="field-error">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <label class="remember-row">
                        <input v-model="form.remember" type="checkbox" />
                        Remember me
                    </label>

                    <button
                        type="submit"
                        class="submit-btn"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? "Signing in..." : "Sign in" }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap");

.login-page {
    --text-main: #0f172a;
    --text-muted: #64748b;
    --accent-primary: #10b981;
    --accent-sec: #0284c7;
    --border: #e2e8f0;

    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    font-family: "Plus Jakarta Sans", sans-serif;
    background: #ffffff;
}

.login-shell {
    width: 100%;
    max-width: 420px;
}

.back-link {
    display: inline-block;
    margin-bottom: 1rem;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    transition: color 0.2s ease;
}

.back-link:hover {
    color: var(--accent-primary);
}

.login-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    padding: 2rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.login-header {
    text-align: center;
    margin-bottom: 1.75rem;
}

.login-logo {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    display: block;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25);
}

.login-eyebrow {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--accent-primary);
    margin-bottom: 0.35rem;
}

.login-header h1 {
    font-family: "Outfit", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text-main);
    margin: 0;
}

.login-subtitle {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.field label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-main);
}

.field input {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 0.65rem;
    padding: 0.7rem 0.85rem;
    font-size: 0.95rem;
    color: var(--text-main);
    background: #ffffff;
    outline: none;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.field input:focus {
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.field-error {
    margin-top: 0.35rem;
    font-size: 0.8rem;
    color: #dc2626;
}

.remember-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--text-muted);
    cursor: pointer;
}

.remember-row input {
    accent-color: var(--accent-primary);
}

.submit-btn {
    margin-top: 0.25rem;
    width: 100%;
    border: none;
    border-radius: 0.65rem;
    padding: 0.8rem 1rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #ffffff;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    cursor: pointer;
    transition:
        transform 0.2s ease,
        opacity 0.2s ease;
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
