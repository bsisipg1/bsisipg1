<script setup>
import AdminLayout from "../../layouts/AdminLayout.vue";
import { useFlashToast } from "../../composables/useFlashToast";

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

useFlashToast();

const adminCount = props.users.filter((user) => user.role === "admin").length;
const appUserCount = props.users.filter((user) => user.role === "appuser").length;

const initials = (name) => {
    const parts = String(name ?? "")
        .trim()
        .split(/\s+/)
        .filter(Boolean);

    if (parts.length === 0) {
        return "?";
    }

    if (parts.length === 1) {
        return parts[0].charAt(0).toUpperCase();
    }

    return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
};
</script>

<template>
    <AdminLayout
        title="Users"
        description="Review administrator and mobile app user accounts."
    >
        <div class="page-toolbar">
            <div class="toolbar-copy">
                <h2>Registered Users</h2>
                <p>
                    {{ users.length }} account(s) · {{ adminCount }} admin ·
                    {{ appUserCount }} app user(s)
                </p>
            </div>
        </div>

        <section v-if="users.length" class="table-shell">
            <div class="table-scroll">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Sign-in</th>
                            <th>Verified</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td>
                                <div class="user-cell">
                                    <img
                                        v-if="user.profile_photo_url"
                                        :src="user.profile_photo_url"
                                        :alt="user.name"
                                        class="user-avatar"
                                    />
                                    <span
                                        v-else
                                        class="user-avatar placeholder"
                                    >
                                        {{ initials(user.name) }}
                                    </span>
                                    <strong class="user-name">{{
                                        user.name
                                    }}</strong>
                                </div>
                            </td>
                            <td class="email-cell">{{ user.email }}</td>
                            <td>
                                <span
                                    class="role-pill"
                                    :class="user.role"
                                >
                                    {{ user.role_label }}
                                </span>
                            </td>
                            <td>
                                <span class="sign-in-pill">
                                    {{
                                        user.uses_google
                                            ? "Google"
                                            : "Email / Password"
                                    }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="verified-pill"
                                    :class="{
                                        verified: user.email_verified,
                                    }"
                                >
                                    {{
                                        user.email_verified
                                            ? "Verified"
                                            : "Unverified"
                                    }}
                                </span>
                            </td>
                            <td>{{ user.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-else class="empty-state">
            <div class="empty-icon">U</div>
            <h3>No users yet</h3>
            <p>
                User accounts will appear here when administrators or app users
                register.
            </p>
        </section>
    </AdminLayout>
</template>

<style scoped>
.page-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem 1.1rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
}

.toolbar-copy h2 {
    font-family: "Outfit", sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
}

.toolbar-copy p {
    margin-top: 0.2rem;
    font-size: 0.82rem;
    color: #64748b;
}

.table-shell {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    overflow: hidden;
}

.table-scroll {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 52rem;
}

.users-table th,
.users-table td {
    padding: 0.85rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.users-table th {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
}

.users-table tbody tr:hover {
    background: rgba(16, 185, 129, 0.03);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 999px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}

.user-avatar.placeholder {
    display: grid;
    place-items: center;
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    font-size: 0.82rem;
    font-weight: 800;
}

.user-name {
    font-size: 0.88rem;
    color: #0f172a;
}

.email-cell {
    font-size: 0.84rem;
    color: #475569;
}

.role-pill,
.sign-in-pill,
.verified-pill {
    display: inline-block;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
}

.role-pill.admin {
    background: rgba(2, 132, 199, 0.1);
    color: #0284c7;
}

.role-pill.appuser {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.sign-in-pill {
    background: #f1f5f9;
    color: #475569;
}

.verified-pill {
    background: rgba(148, 163, 184, 0.18);
    color: #64748b;
}

.verified-pill.verified {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 0.85rem;
}

.empty-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 1rem;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    font-size: 1.35rem;
    font-weight: 700;
}

.empty-state h3 {
    font-family: "Outfit", sans-serif;
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f172a;
}

.empty-state p {
    margin: 0.55rem auto 0;
    max-width: 26rem;
    font-size: 0.9rem;
    color: #64748b;
}

@media (max-width: 720px) {
    .page-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
