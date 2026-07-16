import { defineRouting } from "next-intl/routing";

/**
 * Configurazione di routing i18n condivisa.
 * `localePrefix: 'always'` è obbligatorio con lo static export:
 * senza middleware ogni pagina deve vivere sotto un segmento `/it` o `/en`.
 */
export const routing = defineRouting({
  locales: ["it", "en"],
  defaultLocale: "it",
  localePrefix: "always",
});

export type Locale = (typeof routing.locales)[number];
