import type { Categoria, Progetto, ProgettoSummary } from "@/domain";
import { categoriaSlugSchema } from "@/domain";

/**
 * Mapper dominio → dominio/ViewModel. Isola la derivazione dei dati di
 * riepilogo (card/rail) dalla scheda completa, così la UI resta dichiarativa.
 */

/** Deriva il riepilogo (card) dalla scheda completa: copertina = 1ª foto. */
export function progettoToSummary(progetto: Progetto): ProgettoSummary {
  return {
    id: progetto.id,
    slug: progetto.slug,
    titolo: progetto.titolo,
    cliente: progetto.cliente,
    luogo: progetto.luogo,
    anno: progetto.anno,
    categoria: progetto.categoria,
    settore: progetto.settore,
    inEvidenza: progetto.inEvidenza,
    copertina: progetto.galleria[0],
  };
}

/** Ordinamento di default: più recenti prima (anno decrescente, poi titolo). */
export function byRecent(a: ProgettoSummary, b: ProgettoSummary): number {
  if (b.anno !== a.anno) return b.anno - a.anno;
  return a.titolo.localeCompare(b.titolo, "it");
}

/** Ordine canonico delle categorie (come da tassonomia). */
const CATEGORIA_ORDER = categoriaSlugSchema.options;

/** Categorie presenti nel dataset, in ordine canonico (per filtri/form). */
export function categorieFrom(summaries: ProgettoSummary[]): Categoria[] {
  const bySlug = new Map<string, Categoria>();
  for (const p of summaries) bySlug.set(p.categoria.slug, p.categoria);
  return CATEGORIA_ORDER.map((slug) => bySlug.get(slug)).filter(
    (c): c is Categoria => Boolean(c)
  );
}

/** Settori presenti nel dataset (ordine alfabetico). */
export function settoriFrom(summaries: ProgettoSummary[]): string[] {
  const set = new Set<string>();
  for (const p of summaries) if (p.settore) set.add(p.settore);
  return [...set].sort((a, b) => a.localeCompare(b, "it"));
}

/** Anni presenti nel dataset (decrescente). */
export function anniFrom(summaries: ProgettoSummary[]): number[] {
  const set = new Set<number>();
  for (const p of summaries) set.add(p.anno);
  return [...set].sort((a, b) => b - a);
}
