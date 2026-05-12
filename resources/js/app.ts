import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js'; // Import this
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';
import Toast, {POSITION, type PluginOptions} from 'vue-toastification';
import 'vue-toastification/dist/index.css';


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const toastOptions: PluginOptions = {
    position: POSITION.TOP_CENTER,
    timeout: 3000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button",
    icon: true,
    rtl: false
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Toast, toastOptions)
            .use(ZiggyVue) // ADD THIS LINE HERE
            .mount(el);
    },
    progress: { color: '#4B5563' },
});

initializeTheme();
