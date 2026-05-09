import { useI18n } from 'vue-i18n';
import { RTL_LOCALES, type SupportedLocale } from '@/i18n';

export function useLocale() {
    const { locale } = useI18n();

    const isRtl = () => RTL_LOCALES.includes(locale.value as SupportedLocale);

    const changeLocale = (lang: SupportedLocale) => {
        locale.value = lang;
        localStorage.setItem('spotlight-locale', lang);

        // Update HTML dir and lang attributes for RTL support
        const html = document.documentElement;
        html.setAttribute('lang', lang);

        if (RTL_LOCALES.includes(lang)) {
            html.setAttribute('dir', 'rtl');
        } else {
            html.setAttribute('dir', 'ltr');
        }
    };

    const currentLocale = () => locale.value as SupportedLocale;

    // Initialize dir/lang on first call
    const initLocale = () => {
        const lang = locale.value as SupportedLocale;
        const html = document.documentElement;
        html.setAttribute('lang', lang);
        html.setAttribute('dir', RTL_LOCALES.includes(lang) ? 'rtl' : 'ltr');
    };

    return {
        locale,
        isRtl,
        changeLocale,
        currentLocale,
        initLocale,
    };
}
