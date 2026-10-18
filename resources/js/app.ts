import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, createSSRApp, DefineComponent, h } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'A-RAICE';

void createInertiaApp({
    title: (title) =>
        title && title !== appName ? `${title} - ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const isServer = typeof window === 'undefined';
        const app = (isServer ? createSSRApp : createApp)({
            render: () => h(App, props),
        }).use(plugin);

        if (isServer) {
            return app;
        }

        app.mount(el);

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
