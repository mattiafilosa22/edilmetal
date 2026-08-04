/**
 * Politica editoriale di visibilità delle realizzazioni.
 *
 * Alcune commesse non sono pubblicabili (mancata liberatoria del committente):
 * il contenuto resta in WordPress — non viene cancellato — ma il frontend non
 * lo espone in nessuna superficie pubblica (liste, rail, scheda, sitemap).
 *
 * Per ripubblicarne una basta toglierla da questo elenco.
 */
const PROGETTI_NASCOSTI: readonly string[] = [
  "strutture-miste-aiassa",
  "scale-pinko",
  "pensiline-pinko",
];

/**
 * Polylang assegna alla traduzione uno slug con suffisso numerico
 * (`scale-pinko-2`): il confronto avviene quindi anche sullo slug normalizzato.
 */
export function isProgettoNascosto(slug: string): boolean {
  const base = slug.replace(/-\d+$/, "");
  return PROGETTI_NASCOSTI.includes(slug) || PROGETTI_NASCOSTI.includes(base);
}
