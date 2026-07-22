import { z } from "zod";

/**
 * Entità di dominio "Progetto" (realizzazione / case study) e relativi schemi
 * zod. Gli schemi validano i DTO REST (`wp-json/edilmetal/v1/progetti*`) al
 * confine: nessun dato non validato entra nel resto dell'applicazione.
 *
 * Fonte di verità del contratto API (vedi `docs/api-contract.md`).
 */

/** Le 8 famiglie di opere (tassonomia `categoria_opera`). Slug stabili. */
export const categoriaSlugSchema = z.enum([
  "strutture-acciaio",
  "strutture-miste",
  "scale",
  "pensiline",
  "pensiline-auto",
  "coperture-tamponamenti",
  "rivestimenti-facciata",
  "opere-speciali",
]);
export type CategoriaSlug = z.infer<typeof categoriaSlugSchema>;

/** Termine di categoria: slug canonico + nome visualizzato (editabile in WP). */
export const categoriaSchema = z.object({
  slug: categoriaSlugSchema,
  nome: z.string().min(1),
});
export type Categoria = z.infer<typeof categoriaSchema>;

/** Immagine con dati sufficienti a un rendering responsive senza layout shift. */
export const imageSchema = z.object({
  src: z.string().min(1),
  srcset: z.string().optional(),
  width: z.number().int().positive(),
  height: z.number().int().positive(),
  alt: z.string(),
});
export type Image = z.infer<typeof imageSchema>;

/** Voce di scheda tecnica (etichetta + valore già formattato). */
export const datoTecnicoSchema = z.object({
  label: z.string().min(1),
  valore: z.string().min(1),
});
export type DatoTecnico = z.infer<typeof datoTecnicoSchema>;

/** Meta SEO editoriali (per pagina/progetto); tutti opzionali con fallback UI. */
export const seoMetaSchema = z.object({
  title: z.string().optional(),
  description: z.string().optional(),
  ogImage: z.string().optional(),
});
export type SeoMeta = z.infer<typeof seoMetaSchema>;

/** Campi comuni tra scheda completa e riepilogo (card). */
const progettoBaseSchema = z.object({
  id: z.string().min(1),
  slug: z.string().min(1),
  titolo: z.string().min(1),
  cliente: z.string().min(1),
  luogo: z.string().min(1),
  anno: z.number().int(),
  categoria: categoriaSchema,
  settore: z.string().optional(),
  inEvidenza: z.boolean().default(false),
});

/** Riepilogo usato nelle griglie/rail: immagine di copertina singola. */
export const progettoSummarySchema = progettoBaseSchema.extend({
  copertina: imageSchema,
});
export type ProgettoSummary = z.infer<typeof progettoSummarySchema>;

/** Scheda completa: galleria, dati tecnici, lavorazioni, materiali, SEO. */
export const progettoSchema = progettoBaseSchema.extend({
  descrizione: z.string().min(1),
  galleria: z.array(imageSchema).min(1),
  datiTecnici: z.array(datoTecnicoSchema).default([]),
  lavorazioni: z.array(z.string().min(1)).default([]),
  materiali: z.array(z.string().min(1)).default([]),
  seo: seoMetaSchema.optional(),
});
export type Progetto = z.infer<typeof progettoSchema>;

export const progettoSummaryListSchema = z.array(progettoSummarySchema);

/**
 * Titolo da mostrare per una realizzazione: "Titolo — Cliente", oppure solo
 * il titolo quando i due campi coincidono (caso dei progetti storici seedati
 * con `cliente` usato come placeholder di `titolo`), per evitare la
 * ripetizione "Bervini — Bervini" in UI, breadcrumb e JSON-LD.
 */
export function progettoDisplayName(progetto: { titolo: string; cliente: string }): string {
  return progetto.titolo === progetto.cliente
    ? progetto.titolo
    : `${progetto.titolo} — ${progetto.cliente}`;
}

/** Filtri applicabili alla lista realizzazioni (REST + client-side). */
export type ProgettoFilters = {
  categoria?: CategoriaSlug;
  settore?: string;
  anno?: number;
  inEvidenza?: boolean;
};
