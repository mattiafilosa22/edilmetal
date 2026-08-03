import sanitizeHtml from "sanitize-html";

/** Solo i tag/attributi di formattazione usati nei testi editoriali di WordPress. */
const OPTIONS: sanitizeHtml.IOptions = {
  allowedTags: ["p", "h2", "h3", "ul", "ol", "li", "strong", "em", "a", "br"],
  allowedAttributes: { a: ["href", "target", "rel"] },
};

/**
 * Sanifica l'HTML editoriale (descrizione progetto, testi legali, ...) prima
 * di un rendering `dangerouslySetInnerHTML`: il contenuto viene dal
 * back-office, non da input utente, ma un editor non amministratore
 * potrebbe comunque incollarvi markup non previsto.
 */
export function sanitizeContentHtml(html: string): string {
  return sanitizeHtml(html, OPTIONS);
}
