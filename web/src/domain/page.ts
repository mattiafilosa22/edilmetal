import { z } from "zod";
import { categoriaSlugSchema, seoMetaSchema } from "./progetto";

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

/** Passo numerato di un processo (flow). */
export const stepSchema = z.object({
  titolo: z.string().min(1),
  testo: z.string().min(1),
});
export type Step = z.infer<typeof stepSchema>;

/** Punto di forza / valore (icona scelta per indice nella UI). */
export const featureSchema = z.object({
  titolo: z.string().min(1),
  testo: z.string().min(1),
});
export type Feature = z.infer<typeof featureSchema>;

/** Voce dell'indice delle famiglie di opere (rimanda a Realizzazioni). */
export const categoriaRefSchema = z.object({
  slug: categoriaSlugSchema,
  nome: z.string().min(1),
  dettaglio: z.string().min(1),
});
export type CategoriaRef = z.infer<typeof categoriaRefSchema>;

/** Riquadro tecnico evidenziato (callout). */
export const calloutSchema = z.object({
  titolo: z.string().min(1),
  testo: z.string().min(1),
});
export type Callout = z.infer<typeof calloutSchema>;

/** Banda CTA di fine pagina. */
export const ctaBandSchema = z.object({
  titolo: z.string().min(1),
  testo: z.string().min(1),
});
export type CtaBand = z.infer<typeof ctaBandSchema>;

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

export const homeContentSchema = z.object({
  hero: homeHeroSchema,
  stats: z.array(statSchema).default([]),
  statsIntro: featureSchema,
  categorie: z.array(categoriaRefSchema).default([]),
  processo: z.array(stepSchema).default([]),
  perche: z.array(featureSchema).default([]),
  referenze: z.array(z.string().min(1)).default([]),
  cta: ctaBandSchema,
});
export type HomeContent = z.infer<typeof homeContentSchema>;

/* -------------------------------------------------------------------------- */
/* SERVIZI                                                                     */
/* -------------------------------------------------------------------------- */

export const serviziContentSchema = z.object({
  processo: z.array(stepSchema).default([]),
  tipologie: z.array(categoriaRefSchema).default([]),
  vantaggi: z.array(featureSchema).default([]),
  callout: calloutSchema.optional(),
  cta: ctaBandSchema,
});
export type ServiziContent = z.infer<typeof serviziContentSchema>;

/* -------------------------------------------------------------------------- */
/* AZIENDA                                                                     */
/* -------------------------------------------------------------------------- */

/** Card della rail "dentro l'officina". */
export const officinaItemSchema = z.object({
  tag: z.string().min(1),
  cliente: z.string().min(1),
  titolo: z.string().min(1),
  luogo: z.string().min(1),
});
export type OfficinaItem = z.infer<typeof officinaItemSchema>;

export const aziendaContentSchema = z.object({
  storiaTitolo: z.string().min(1),
  storia: z.array(z.string().min(1)).default([]),
  stats: z.array(statSchema).default([]),
  valori: z.array(featureSchema).default([]),
  officinaTitolo: z.string().min(1),
  officinaSubtitle: z.string().optional(),
  officina: z.array(officinaItemSchema).default([]),
  sedeTitolo: z.string().min(1),
  /** Zona/area di riferimento (editoriale); l'indirizzo arriva da /settings. */
  zona: z.string().optional(),
  comeArrivare: z.string().optional(),
  cta: ctaBandSchema,
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
