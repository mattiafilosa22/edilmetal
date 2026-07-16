import type { MetadataRoute } from "next";
import { routing, type Locale } from "@/i18n/routing";
import { getProgetti } from "@/lib/api";
import {
  absoluteUrl,
  progettoPath,
  buildLanguageAlternates,
  localePathsFor,
} from "@/lib/seo";

/** Richiesto da `output: 'export'`: emette `out/sitemap.xml` staticamente. */
export const dynamic = "force-static";

/**
 * Sitemap statica generata all'export (`output: 'export'` → `out/sitemap.xml`).
 * Include tutte le route in ENTRAMBE le locali con hreflang reciproci, più una
 * voce per ciascuna realizzazione (slug reali, condivisi tra IT/EN).
 * URL assoluti dalla base configurabile (`NEXT_PUBLIC_SITE_URL`).
 */

/** Segmenti (dopo il locale) delle route statiche, identici in IT/EN. */
const STATIC_ROUTES: string[][] = [
  [],
  ["realizzazioni"],
  ["servizi"],
  ["azienda"],
  ["contatti"],
];

type SitemapEntry = MetadataRoute.Sitemap[number];

function entry(
  paths: Record<Locale, string>,
  priority: number,
  changeFrequency: SitemapEntry["changeFrequency"]
): SitemapEntry {
  return {
    url: absoluteUrl(paths[routing.defaultLocale]),
    changeFrequency,
    priority,
    alternates: { languages: buildLanguageAlternates(paths) },
  };
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const staticEntries = STATIC_ROUTES.map((segments) => {
    const isHome = segments.length === 0;
    return entry(
      localePathsFor(...segments),
      isHome ? 1 : 0.7,
      isHome ? "weekly" : "monthly"
    );
  });

  // Slug delle realizzazioni: uguali tra le locali (nomi propri/tecnici).
  const slugs = new Set<string>();
  for (const locale of routing.locales) {
    const progetti = await getProgetti({ locale });
    for (const p of progetti) slugs.add(p.slug);
  }

  const progettoEntries: MetadataRoute.Sitemap = [];
  for (const slug of slugs) {
    const languages: Record<string, string> = {};
    for (const locale of routing.locales) {
      languages[locale] = absoluteUrl(progettoPath(locale, slug));
    }
    languages["x-default"] = languages[routing.defaultLocale];
    progettoEntries.push({
      url: languages[routing.defaultLocale],
      changeFrequency: "monthly",
      priority: 0.8,
      alternates: { languages },
    });
  }

  return [...staticEntries, ...progettoEntries];
}
