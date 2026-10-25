import 'bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';

type PageModules = Record<string, () => Promise<DefineComponent>>;

const appName = import.meta.env.VITE_APP_NAME || 'LTBS';

export function createPortalApp(
    pages: PageModules,
): void {
    void createInertiaApp({
        title: (title) => (title ? `${title} | ${appName}` : appName),
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.vue`,
                pages,
            ),
        setup({ el, App, props, plugin }) {
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el);
        },
    });
}
