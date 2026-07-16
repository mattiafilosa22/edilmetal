import { leadSchema, type Lead } from "@/domain";

/**
 * Modulo condiviso per l'invio dei lead (richieste di preventivo).
 * Disaccoppiato dai form: esegue un `POST` JSON all'endpoint WordPress
 * `wp-json/edilmetal/v1/lead`. La finalizzazione (mapping Fluent Forms) è lato CMS.
 *
 * L'URL base runtime arriva da `NEXT_PUBLIC_WP_API_URL` (esposto al client):
 * in export statico il browser deve conoscere l'origine assoluta del CMS.
 */

const DEFAULT_API_BASE = "/wp-json/edilmetal/v1";

export type LeadRequest = Lead;

export function leadApiBase(): string {
  const fromEnv = process.env.NEXT_PUBLIC_WP_API_URL;
  return fromEnv && fromEnv.length > 0 ? fromEnv : DEFAULT_API_BASE;
}

export function leadEndpoint(base: string = leadApiBase()): string {
  return `${base.replace(/\/$/, "")}/lead`;
}

export type SubmitLeadOptions = {
  endpoint?: string;
  fetchImpl?: typeof fetch;
  signal?: AbortSignal;
};

/**
 * Valida il payload col dominio e lo invia. Lancia in caso di dati non validi
 * o risposta non `ok`, così il chiamante può mostrare lo stato di errore.
 */
export async function submitLead(
  payload: LeadRequest,
  options: SubmitLeadOptions = {}
): Promise<void> {
  const lead = leadSchema.parse(payload);

  const doFetch = options.fetchImpl ?? fetch;
  const endpoint = options.endpoint ?? leadEndpoint();

  const response = await doFetch(endpoint, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    // Mappatura camelCase → snake_case attesa dal LeadController WP.
    body: JSON.stringify({
      nome: lead.nome,
      azienda: lead.azienda,
      email: lead.email,
      telefono: lead.telefono,
      tipo_opera: lead.tipoOpera,
      messaggio: lead.messaggio,
      progetto_slug: lead.progettoSlug,
      // Il consenso GDPR di dominio è `gdpr`, l'endpoint lo attende come `consenso`.
      consenso: lead.gdpr,
    }),
    signal: options.signal,
  });

  if (!response.ok) {
    throw new Error(`Invio lead non riuscito (HTTP ${response.status})`);
  }
}
