import { sanitizeContentHtml } from "@/lib/sanitizeHtml";

type LegalContentProps = {
  body: string;
  updatedAtLabel?: string;
};

/**
 * Corpo di una pagina legale (Privacy Policy, Cookie Policy): testo
 * editoriale HTML da WordPress (titoli, paragrafi), sanificato prima del
 * rendering — resta testo del back-office, ma un editor non amministratore
 * potrebbe comunque incollarvi markup non previsto.
 */
export function LegalContent({ body, updatedAtLabel }: LegalContentProps) {
  const safeBody = sanitizeContentHtml(body);

  return (
    <section className="section">
      <div className="container" style={{ maxWidth: "72ch" }}>
        {updatedAtLabel ? (
          <p className="mono" style={{ color: "var(--ink-faint)" }}>
            {updatedAtLabel}
          </p>
        ) : null}
        <div className="legal-body" dangerouslySetInnerHTML={{ __html: safeBody }} />
      </div>
    </section>
  );
}
