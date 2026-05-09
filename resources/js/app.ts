import { createInertiaApp } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';

import AppWrapper from './AppWrapper';
import 'vue-sonner/style.css';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';
import i18n, { RTL_LOCALES, type SupportedLocale } from './i18n';
import './echo';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(AppWrapper, () => h(App, props)) })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Initialize locale direction (RTL/LTR) on page load
const savedLocale = (localStorage.getItem('spotlight-locale') || 'en') as SupportedLocale;
document.documentElement.setAttribute('lang', savedLocale);
document.documentElement.setAttribute('dir', RTL_LOCALES.includes(savedLocale) ? 'rtl' : 'ltr');
