/**
 * Tipi e utilità per i dati strutturati JSON-LD (schema.org).
 * I builder sono funzioni pure e testabili: la serializzazione nel tag
 * `<script type="application/ld+json">` avviene nel componente `JsonLd`.
 */

/** Valore JSON serializzabile (nessun `undefined`). */
export type JsonValue =
  | string
  | number
  | boolean
  | null
  | JsonValue[]
  | { [key: string]: JsonValue };

export type JsonObject = { [key: string]: JsonValue };

/** Struttura in ingresso ai builder: ammette `undefined` da campi opzionali. */
export type JsonInput =
  | string
  | number
  | boolean
  | null
  | undefined
  | JsonInput[]
  | { [key: string]: JsonInput };

function isEmpty(value: JsonValue): boolean {
  if (value === null) return true;
  if (typeof value === "string") return value.trim().length === 0;
  if (Array.isArray(value)) return value.length === 0;
  if (typeof value === "object") return Object.keys(value).length === 0;
  return false;
}

/**
 * Ripulisce ricorsivamente l'albero JSON-LD rimuovendo `undefined`, `null`,
 * stringhe vuote, array e oggetti vuoti. Garantisce che nulla di "assente"
 * finisca serializzato (requisito: nessun campo inventato o `undefined`).
 */
export function pruneJson(value: JsonInput): JsonValue | undefined {
  if (value === undefined || value === null) return undefined;

  if (Array.isArray(value)) {
    const items = value
      .map((item) => pruneJson(item))
      .filter((item): item is JsonValue => item !== undefined && !isEmpty(item));
    return items.length > 0 ? items : undefined;
  }

  if (typeof value === "object") {
    const out: JsonObject = {};
    for (const [key, raw] of Object.entries(value)) {
      const cleaned = pruneJson(raw);
      if (cleaned !== undefined && !isEmpty(cleaned)) out[key] = cleaned;
    }
    return Object.keys(out).length > 0 ? out : undefined;
  }

  if (typeof value === "string" && value.trim().length === 0) return undefined;
  return value;
}

const SCHEMA = "https://schema.org";

/** Aggiunge `@context`/`@type` e ripulisce l'oggetto per la serializzazione. */
export function schemaNode(type: string, body: JsonInput): JsonObject {
  const base = { "@context": SCHEMA, "@type": type };
  const cleaned = pruneJson(body);
  const merged =
    cleaned && typeof cleaned === "object" && !Array.isArray(cleaned)
      ? { ...base, ...cleaned }
      : base;
  return merged;
}
