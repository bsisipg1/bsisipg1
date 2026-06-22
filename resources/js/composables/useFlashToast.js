import { usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { watch } from "vue";

export function useFlashToast() {
    const page = usePage();

    watch(
        () => page.props.flash?.success,
        (message) => {
            if (message) {
                toast.success(message);
            }
        },
        { immediate: true },
    );

    watch(
        () => page.props.flash?.error,
        (message) => {
            if (message) {
                toast.error(message);
            }
        },
        { immediate: true },
    );
}

export function toastFormErrors(errors) {
    const firstError = Object.values(errors ?? {})[0];

    if (firstError) {
        toast.error(Array.isArray(firstError) ? firstError[0] : firstError);
    }
}
