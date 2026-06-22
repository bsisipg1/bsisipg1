<script setup>
defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: "",
    },
    confirmLabel: {
        type: String,
        default: "Confirm",
    },
    cancelLabel: {
        type: String,
        default: "Cancel",
    },
    loading: {
        type: Boolean,
        default: false,
    },
    destructive: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["confirm", "cancel"]);
</script>

<template>
    <Teleport to="body">
        <Transition name="confirm-dialog">
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
                @click.self="emit('cancel')"
            >
                <div
                    role="alertdialog"
                    aria-modal="true"
                    class="w-full max-w-md rounded-lg border bg-background p-6 shadow-lg"
                >
                    <h2 class="text-lg font-semibold text-foreground">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="mt-2 text-sm text-muted-foreground">
                        {{ description }}
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-muted disabled:opacity-50"
                            :disabled="loading"
                            @click="emit('cancel')"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium text-primary-foreground shadow transition-colors disabled:opacity-50"
                            :class="
                                destructive
                                    ? 'bg-destructive hover:bg-destructive/90'
                                    : 'bg-primary hover:bg-primary/90'
                            "
                            :disabled="loading"
                            @click="emit('confirm')"
                        >
                            {{ loading ? "Please wait..." : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.confirm-dialog-enter-active,
.confirm-dialog-leave-active {
    transition: opacity 0.2s ease;
}

.confirm-dialog-enter-active > div:last-child,
.confirm-dialog-leave-active > div:last-child {
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.confirm-dialog-enter-from,
.confirm-dialog-leave-to {
    opacity: 0;
}

.confirm-dialog-enter-from > div:last-child,
.confirm-dialog-leave-to > div:last-child {
    opacity: 0;
    transform: scale(0.96);
}
</style>
