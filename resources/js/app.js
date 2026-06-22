import "./bootstrap";
import "../css/app.css";
import "vue-sonner/style.css";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, Fragment, h } from "vue";
import { Toaster } from "vue-sonner";

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob("./pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () =>
                h(Fragment, [
                    h(App, props),
                    h(Toaster, {
                        richColors: true,
                        position: "top-right",
                        closeButton: true,
                    }),
                ]),
        })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: "#10b981",
    },
});
