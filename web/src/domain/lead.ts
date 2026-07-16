import { z } from "zod";
import { categoriaSlugSchema } from "./progetto";

/**
 * Lead di richiesta preventivo inviato dal form (Fluent Forms lato WP).
 * Lo schema è usato per la validazione client del form contatti.
 * Dominio B2B: nome, azienda, contatti, tipo di opera, messaggio, consenso.
 */
export const leadSchema = z.object({
  nome: z.string().min(2),
  azienda: z.string().optional(),
  email: z.email(),
  telefono: z.string().optional(),
  /** Tipo di opera: slug di categoria opera (allineato alla tassonomia). */
  tipoOpera: categoriaSlugSchema,
  messaggio: z.string().min(10),
  /** Sorgente opzionale (es. slug del progetto dalla scheda). */
  progettoSlug: z.string().optional(),
  gdpr: z.literal(true),
});
export type Lead = z.infer<typeof leadSchema>;
