import { z } from "zod";
import { categoriaSchema, categoriaSlugSchema, imageSchema, seoMetaSchema } from "./progetto";

/**
 * Contenuto editoriale di una pagina, gestito in WordPress
 * (`wp-json/edilmetal/v1/pages/{key}`). I testi NON sono mai hardcoded nel
 * frontend: arrivano da qui (o dal mock in assenza di WP).
 *
 * `key` ∈ home | servizi | azienda | contatti. I blocchi tipizzati sono
 * opzionali: inclusi solo se valorizzati (vedi `docs/api-contract.md`).
 */

/* -------------------------------------------------------------------------- */
/* Blocchi condivisi                                                          */
/* -------------------------------------------------------------------------- */

/** Call to action editoriale: etichetta + destinazione (path relativo). */
export const ctaSchema = z.object({
  label: z.string().min(1),
  href: z.string().min(1),
});
export type Cta = z.infer<typeof ctaSchema>;

/** Statistica sintetica (valore + etichetta). */
export const statSchema = z.object({
  valore: z.string().min(1),
  etichetta: z.string().min(1),
});
export type Stat = z.infer<typeof statSchema>;

/** Voce dell'indice delle famiglie di opere (rimanda a Realizzazioni). */
export const categoriaRefSchema = z.object({
  slug: categoriaSlugSchema,
  nome: z.string().min(1),
  dettaglio: z.string().min(1),
});
export type CategoriaRef = z.infer<typeof categoriaRefSchema>;

/* -------------------------------------------------------------------------- */
/* HOME                                                                       */
/* -------------------------------------------------------------------------- */

export const homeHeroSchema = z.object({
  eyebrow: z.string().optional(),
  title: z.string().min(1),
  titleAccent: z.string().optional(),
  subtitle: z.string().min(1),
  ctaPrimary: ctaSchema,
  ctaSecondary: ctaSchema.optional(),
  /** Barra-indice mono sotto l'hero (max 3 voci). */
  index: z.array(statSchema).default([]),
});
export type HomeHero = z.infer<typeof homeHeroSchema>;

/** Categoria "in evidenza" in home: foto reale + link alla sezione prodotti. */
export const homeFeaturedSchema = z.object({
  categoria: categoriaSchema,
  immagine: imageSchema,
});
export type HomeFeatured = z.infer<typeof homeFeaturedSchema>;

export const homeContentSchema = z.object({
  hero: homeHeroSchema,
  /** "In evidenza": fino a 2 categorie con foto reale (blocco storico del sito). */
  inEvidenza: z.array(homeFeaturedSchema).max(2).default([]),
});
export type HomeContent = z.infer<typeof homeContentSchema>;

/* -------------------------------------------------------------------------- */
/* SERVIZI                                                                     */
/* -------------------------------------------------------------------------- */

export const serviziContentSchema = z.object({
  tipologie: z.array(categoriaRefSchema).default([]),
});
export type ServiziContent = z.infer<typeof serviziContentSchema>;

/* -------------------------------------------------------------------------- */
/* AZIENDA                                                                     */
/* -------------------------------------------------------------------------- */

export const aziendaContentSchema = z.object({
  storiaTitolo: z.string().min(1),
  storia: z.array(z.string().min(1)).default([]),
});
export type AziendaContent = z.infer<typeof aziendaContentSchema>;

/* -------------------------------------------------------------------------- */
/* CONTATTI                                                                    */
/* -------------------------------------------------------------------------- */

export const contattiContentSchema = z.object({
  /** Testo introduttivo opzionale (i riferimenti arrivano da /settings). */
  intro: z.string().optional(),
});
export type ContattiContent = z.infer<typeof contattiContentSchema>;

/* -------------------------------------------------------------------------- */
/* PageContent                                                                 */
/* -------------------------------------------------------------------------- */

export const pageContentSchema = z.object({
  key: z.string().min(1),
  title: z.string().min(1),
  subtitle: z.string().optional(),
  seo: seoMetaSchema.optional(),
  home: homeContentSchema.optional(),
  servizi: serviziContentSchema.optional(),
  azienda: aziendaContentSchema.optional(),
  contatti: contattiContentSchema.optional(),
});
export type PageContent = z.infer<typeof pageContentSchema>;
