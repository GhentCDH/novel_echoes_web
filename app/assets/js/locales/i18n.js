import { createI18n } from "vue-i18n";

import en from "./en.json";
import fr from "./fr.json";

export const i18n = createI18n({
    legacy: false, // needed for composition api
    locale: window.location.pathname.split('/')[1] || "en",
    fallbackLocale: "en",
    messages: {
        en, fr
    },
    globalInjection: true,
});