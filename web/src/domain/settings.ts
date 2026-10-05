import { z } from "zod";
import { imageSchema } from "./progetto";

/**
 * Impostazioni del sito editabili in WordPress
 * (`wp-json/edilmetal/v1/settings`). Contatti, orari, coordinate, social,
 * dati legali. Dominio B2B su commessa: nessun prezzo/whatsapp.
 */

export const orarioSchema = z.object({
  giorni: z.string().min(1),
  apertura: z.string(),
});
export type Orario = z.infer<typeof orarioSchema>;

export const socialSchema = z.object({
  facebook: z.string().optional(),
  instagram: z.string().optional(),
  linkedin: z.string().optional(),
});
export type Social = z.infer<typeof socialSchema>;

/** Coordinate geografiche della sede (marker mappa Leaflet). */
export const coordinateSchema = z.object({
  lat: z.number(),
  lng: z.number(),
});
export type Coordinate = z.infer<typeof coordinateSchema>;

export const siteSettingsSchema = z.object({
  nomeAzienda: z.string().min(1),
  ragioneSociale: z.string().min(1),
  partitaIva: z.string().min(1),
  indirizzo: z.string().min(1),
  telefono: z.string().min(1),
  /**
   * Fax (opzionale, dato storico del sito precedente). Resta nel contratto
   * API del CMS ma non è pubblicato: nessun componente lo mostra.
   */
  fax: z.string().optional(),
  email: z.email(),
  /** Coordinate della sede per la mappa Leaflet (OSM cookieless). */
  coordinate: coordinateSchema,
  /** Link esterno alle mappe (opzionale); assente ⇒ fallback OpenStreetMap. */
  mapsUrl: z.string().optional(),
  orari: z.array(orarioSchema).default([]),
  social: socialSchema.default({}),
  /** Foto hero della homepage, editabile globalmente in WP (Impostazioni). */
  heroImage: imageSchema.optional(),
  /** Credito fotografico mostrato accanto all'hero/footer. */
  fotoCredit: z.string().optional(),
});
export type SiteSettings = z.infer<typeof siteSettingsSchema>;
