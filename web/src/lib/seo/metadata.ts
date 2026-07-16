import type { Metadata } from "next";
import { routing, type Locale } from "@/i18n/routing";
import { OG_LOCALE, absoluteUrl } from "./site";

/**
 * Costruzione centralizzata dei metadata SEO per pagina: title assoluto,
 * description, canonical, hreflang reciproci (+ x-default), Open Graph e
 * Twitter card. Un solo punto di verità → coerenza su tutte le route.
 */

/** Immagine social (assoluta) usata da Open Graph / Twitter. */
export type SeoImage = {
  url: string;
  width?: number;
  height?: number;
  alt?: string;
};

export type SeoInput = {
  locale: Locale;
  /**
   * Path per locale (senza dominio, con trailing slash). Può essere parziale:
   * una lingua senza controparte verificabile (es. traduzione assente) viene
   * semplicemente omessa dagli hreflang invece di puntare a un URL sbagliato.
   */
  paths: Partial<Record<Locale, string>>;
  /** Nome del sito (suffisso del title e `og:site_name`). */
  siteName: string;
  /** Titolo del contenuto: diventa "[heading] — [siteName]". Omesso → solo siteName. */
  heading?: string;
  description: string;
  image?: SeoImage;
  ogType?: "website" | "article";
};

/** Immagine OG di default servita da `public/`. */
export const DEFAULT_OG_IMAGE = {
  path: "/og-default.png",
  width: 1200,
  height: 630,
} as const;

/** hreflang reciproci per le locali disponibili, con `x-default`. */
export function buildLanguageAlternates(
  paths: Partial<Record<Locale, string>>
): Record<string, string> {
  const languages: Record<string, string> = {};
  for (const locale of routing.locales) {
    const path = paths[locale];
    if (path) languages[locale] = absoluteUrl(path);
  }
  const fallback = paths[routing.defaultLocale] ?? Object.values(paths)[0];
  if (fallback) languages["x-default"] = absoluteUrl(fallback);
  return languages;
}

export function buildMetadata(input: SeoInput): Metadata {
  const { locale, paths, siteName, heading, description, ogType = "website" } =
    input;

  const fullTitle = heading ? `${heading} — ${siteName}` : siteName;
  const currentPath = paths[locale] ?? paths[routing.defaultLocale];
  const canonical = currentPath ? absoluteUrl(currentPath) : undefined;
  const languages = buildLanguageAlternates(paths);

  const image: SeoImage = input.image ?? {
    url: absoluteUrl(DEFAULT_OG_IMAGE.path),
    width: DEFAULT_OG_IMAGE.width,
    height: DEFAULT_OG_IMAGE.height,
    alt: siteName,
  };

  return {
    title: { absolute: fullTitle },
    description,
    alternates: { canonical, languages },
    openGraph: {
      type: ogType,
      title: fullTitle,
      description,
      url: canonical,
      siteName,
      locale: OG_LOCALE[locale],
      images: [
        {
          url: image.url,
          width: image.width,
          height: image.height,
          alt: image.alt ?? fullTitle,
        },
      ],
    },
    twitter: {
      card: "summary_large_image",
      title: fullTitle,
      description,
      images: [image.url],
    },
  };
}
