import { routing, type Locale } from "@/i18n/routing";

/**
 * Origine del sito per URL assoluti (canonical, hreflang, sitemap, OG).
 * Configurabile via `NEXT_PUBLIC_SITE_URL`; default sensato di produzione.
 * Nessun host localhost hardcoded: in build senza env si usa il dominio reale.
 */
export const SITE_URL = (
  process.env.NEXT_PUBLIC_SITE_URL ?? "https://www.edilmetal.it"
).replace(/\/$/, "");

/**
 * Compone un URL assoluto a partire da un path relativo alla root del sito.
 * Se il valore è già un URL assoluto (es. immagini servite dal CMS), lo
 * restituisce invariato per non produrre URL doppiati.
 */
export function absoluteUrl(path: string): string {
  if (/^https?:\/\//i.test(path)) return path;
  const clean = path.startsWith("/") ? path : `/${path}`;
  return `${SITE_URL}${clean}`;
}

/**
 * Path interno (senza dominio) di una route, con trailing slash coerente con
 * `next.config.ts` (`trailingSlash: true`). Segmenti vuoti → home del locale.
 */
export function localePath(locale: Locale, ...segments: string[]): string {
  const parts = [locale, ...segments].filter((part) => part.length > 0);
  return `/${parts.join("/")}/`;
}

/** Path assoluto della scheda realizzazione per un dato locale e slug. */
export function progettoPath(locale: Locale, slug: string): string {
  return localePath(locale, "realizzazioni", slug);
}

/**
 * Mappa locale → path per pagine con segmenti identici tra le lingue
 * (gli slug editoriali sono uguali in IT/EN per queste route).
 */
export function localePathsFor(...segments: string[]): Record<Locale, string> {
  const paths = {} as Record<Locale, string>;
  for (const locale of routing.locales) {
    paths[locale] = localePath(locale, ...segments);
  }
  return paths;
}

/** Codici locale per Open Graph (`og:locale`). */
export const OG_LOCALE: Record<Locale, string> = {
  it: "it_IT",
  en: "en_GB",
};
