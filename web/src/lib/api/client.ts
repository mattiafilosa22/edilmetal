import type { z } from "zod";

/**
 * Client REST minimale usato a BUILD TIME (static export).
 * Legge `WP_API_URL` (es. http://localhost:8888/wp-json/edilmetal/v1) e valida
 * ogni risposta con zod al confine. Non introduce dipendenze da runtime server.
 */

const API_URL = process.env.WP_API_URL;

export function isApiConfigured(): boolean {
  return typeof API_URL === "string" && API_URL.length > 0;
}

/**
 * Header Basic Auth opzionale, per buildare contro ambienti protetti (es. un
 * ambiente di test dietro basic auth). Formato env `WP_API_BASIC_AUTH="utente:password"`.
 * Assente ⇒ nessun header (comportamento invariato).
 */
function buildAuthHeaders(): Record<string, string> {
  const creds = process.env.WP_API_BASIC_AUTH;
  if (!creds) return {};
  return { Authorization: `Basic ${Buffer.from(creds).toString("base64")}` };
}

/**
 * Modalità STRICT: con `WP_API_STRICT=1` il data layer NON ricade sul mock.
 * Ogni errore di fetch o di validazione zod viene propagato, così il build
 * statico fallisce e i disallineamenti col CMS live emergono subito.
 */
export function isStrict(): boolean {
  return process.env.WP_API_STRICT === "1";
}

export class ApiError extends Error {
  constructor(message: string, options?: { cause?: unknown }) {
    super(message, options);
    this.name = "ApiError";
  }
}

type FetchParams = Record<string, string | undefined>;

function buildUrl(path: string, params?: FetchParams): string {
  const base = API_URL!.replace(/\/$/, "");
  const url = new URL(`${base}/${path.replace(/^\//, "")}`);
  if (params) {
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined) url.searchParams.set(key, value);
    }
  }
  return url.toString();
}

/**
 * Esegue la fetch e valida con lo schema fornito.
 * Un 404 restituisce `null` (risorsa assente); ogni altro errore lancia
 * `ApiError`, così il chiamante può decidere il fallback al mock.
 */
/** Status transitori (rate/resource limit, gateway) su cui ritentare. */
const RETRYABLE_STATUS = new Set([408, 425, 429, 500, 502, 503, 504, 508]);
const MAX_ATTEMPTS = 5;

const sleep = (ms: number): Promise<void> =>
  new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Fetch con retry a backoff esponenziale + jitter. Robusto contro i limiti
 * di risorsa transitori dell'hosting (es. CloudLinux LVE → HTTP 508) quando il
 * build statico interroga l'API in parallelo. Un 404 NON è ritentato (risorsa
 * assente, gestito a monte).
 */
async function fetchWithRetry(
  url: string,
  init: RequestInit
): Promise<Response> {
  let lastError: unknown;
  for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
    try {
      const response = await fetch(url, init);
      if (RETRYABLE_STATUS.has(response.status) && attempt < MAX_ATTEMPTS) {
        await sleep(400 * 2 ** (attempt - 1) + Math.floor(Math.random() * 300));
        continue;
      }
      return response;
    } catch (error) {
      lastError = error;
      if (attempt >= MAX_ATTEMPTS) break;
      await sleep(400 * 2 ** (attempt - 1) + Math.floor(Math.random() * 300));
    }
  }
  throw lastError instanceof Error ? lastError : new Error(String(lastError));
}

export async function fetchValidated<S extends z.ZodTypeAny>(
  path: string,
  schema: S,
  params?: FetchParams
): Promise<z.infer<S> | null> {
  if (!isApiConfigured()) {
    throw new ApiError("WP_API_URL non configurata");
  }

  let response: Response;
  try {
    response = await fetchWithRetry(buildUrl(path, params), {
      headers: { Accept: "application/json", ...buildAuthHeaders() },
    });
  } catch (error) {
    throw new ApiError(`Fetch fallita per ${path}`, { cause: error });
  }

  if (response.status === 404) return null;
  if (!response.ok) {
    throw new ApiError(`Risposta ${response.status} per ${path}`);
  }

  let json: unknown;
  try {
    json = await response.json();
  } catch (error) {
    throw new ApiError(`JSON non valido per ${path}`, { cause: error });
  }

  const parsed = schema.safeParse(json);
  if (!parsed.success) {
    throw new ApiError(`Validazione zod fallita per ${path}`, {
      cause: parsed.error,
    });
  }
  return parsed.data;
}
