import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import fr from './locales/fr.json';
import ar from './locales/ar.json';

export type SupportedLocale = 'en' | 'fr' | 'ar';

export const RTL_LOCALES: SupportedLocale[] = ['ar'];

function getInitialLocale(): SupportedLocale {
    // 1. Check localStorage
    const saved = localStorage.getItem('spotlight-locale') as SupportedLocale | null;
    if (saved && ['en', 'fr', 'ar'].includes(saved)) {
        return saved;
    }

    // 2. Check browser language
    const browserLang = navigator.language?.split('-')[0] as SupportedLocale;
    if (['en', 'fr', 'ar'].includes(browserLang)) {
        return browserLang;
    }

    // 3. Default to English
    return 'en';
}

const i18n = createI18n({
    legacy: false, // Use Composition API mode
    locale: getInitialLocale(),
    fallbackLocale: 'en',
    messages: {
        en,
        fr,
        ar,
    },
});

export default i18n;
