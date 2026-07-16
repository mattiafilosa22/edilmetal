import type { JsonValue } from "./jsonLd";

/**
 * Serializza un nodo JSON-LD in `<script type="application/ld+json">`.
 * Render lato server (RSC): il markup è statico e coincide col client, quindi
 * non rompe l'idratazione. `<` viene escapato per evitare rotture del tag.
 * Il contenuto è JSON prodotto dai builder su dati validati (zod), non HTML.
 */
export function JsonLd({ data }: { data: JsonValue | JsonValue[] }) {
  const json = JSON.stringify(data).replace(/</g, "\\u003c");
  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: json }}
    />
  );
}
