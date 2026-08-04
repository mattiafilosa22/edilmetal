import {
  isProgettoNascosto,
  progettoSchema,
  progettoSummaryListSchema,
  pageContentSchema,
  siteSettingsSchema,
  type Progetto,
  type ProgettoFilters,
  type ProgettoSummary,
  type PageContent,
  type SiteSettings,
} from "@/domain";
import { routing, type Locale } from "@/i18n/routing";
import { progettoToSummary } from "@/lib/mappers/progetto";
import { anonimizzaProgetto, anonimizzaSummary } from "@/lib/anonimizza";
import { ApiError, fetchValidated, isApiConfigured, isStrict } from "./client";
import { mockProgetti } from "./mock/progetti";
import { mockSettings } from "./mock/settings";
import { mockPages } from "./mock/pages";

/**
 * Data layer pubblico (build-time). Ogni funzione prova la REST di WordPress
 * e, se `WP_API_URL` manca o la chiamata/validazione fallisce, ricade sul
 * dataset mock — così `npm run build` funziona anche senza CMS attivo.
 *
 * In modalità STRICT (`WP_API_STRICT=1`) il fallback è disattivato: gli errori
 * vengono propagati per far fallire il build a fronte di disallineamenti col CMS.
 */

const STRICT_NO_URL =
  "WP_API_URL non configurata: modalità strict richiede il CMS live";

/** Variante per risorse non nullable (liste, settings): il null è un errore. */
async function withFallback<T>(
  attempt: () => Promise<T | null>,
  fallback: () => T
): Promise<T> {
  if (!isApiConfigured()) {
    if (isStrict()) throw new ApiError(STRICT_NO_URL);
    return fallback();
  }
  if (isStrict()) {
    const result = await attempt();
    if (result === null) {
      throw new ApiError("Risorsa attesa assente (modalità strict)");
    }
    return result;
  }
  try {
    const result = await attempt();
    return result ?? fallback();
  } catch (error) {
    console.warn("[api] fallback al mock:", (error as Error).message);
    return fallback();
  }
}

/** Variante per risorse nullable (dettaglio, pagina): il null è "non trovato". */
async function withNullableFallback<T>(
  attempt: () => Promise<T | null>,
  fallback: () => T | null
): Promise<T | null> {
  if (!isApiConfigured()) {
    if (isStrict()) throw new ApiError(STRICT_NO_URL);
    return fallback();
  }
  if (isStrict()) return attempt();
  try {
    const result = await attempt();
    return result ?? fallback();
  } catch (error) {
    console.warn("[api] fallback al mock:", (error as Error).message);
    return fallback();
  }
}

/** Applica i filtri (categoria/settore/anno/inEvidenza) al dataset mock. */
function filterMock(
  summaries: ProgettoSummary[],
  filters: ProgettoFilters
): ProgettoSummary[] {
  return summaries.filter((p) => {
    if (filters.categoria && p.categoria.slug !== filters.categoria) return false;
    if (filters.settore && p.settore !== filters.settore) return false;
    if (typeof filters.anno === "number" && p.anno !== filters.anno) return false;
    if (filters.inEvidenza && !p.inEvidenza) return false;
    return true;
  });
}

export async function getProgetti(args: {
  locale: Locale;
  filters?: ProgettoFilters;
}): Promise<ProgettoSummary[]> {
  const { locale, filters = {} } = args;
  const summaries = await withFallback(
    () =>
      fetchValidated("progetti", progettoSummaryListSchema, {
        lang: locale,
        categoria: filters.categoria,
        settore: filters.settore,
        anno: typeof filters.anno === "number" ? String(filters.anno) : undefined,
        inEvidenza: filters.inEvidenza ? "1" : undefined,
      }),
    () => {
      const all = mockProgetti.map(progettoToSummary);
      return progettoSummaryListSchema.parse(filterMock(all, filters));
    }
  );
  return summaries
    .filter((p) => !isProgettoNascosto(p.slug))
    .map(anonimizzaSummary);
}

export async function getProgetto(args: {
  locale: Locale;
  slug: string;
}): Promise<Progetto | null> {
  const { locale, slug } = args;
  if (isProgettoNascosto(slug)) return null;
  const progetto = await withNullableFallback(
    () => fetchValidated(`progetti/${slug}`, progettoSchema, { lang: locale }),
    () => mockProgetti.find((p) => p.slug === slug) ?? null
  );
  return progetto === null ? null : anonimizzaProgetto(progetto);
}

export async function getPage(args: {
  locale: Locale;
  key: string;
}): Promise<PageContent | null> {
  const { locale, key } = args;
  return withNullableFallback(
    () => fetchValidated(`pages/${key}`, pageContentSchema, { lang: locale }),
    () =>
      mockPages[locale]?.[key] ??
      mockPages[routing.defaultLocale]?.[key] ??
      null
  );
}

export async function getSettings(args: {
  locale: Locale;
}): Promise<SiteSettings> {
  return withFallback(
    () => fetchValidated("settings", siteSettingsSchema, { lang: args.locale }),
    () => siteSettingsSchema.parse(mockSettings)
  );
}
