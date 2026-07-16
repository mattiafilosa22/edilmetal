import Link from "next/link";
import { routing } from "@/i18n/routing";

/**
 * Root `/`: reindirizza alla locale di default.
 * In static export non c'è middleware, quindi il redirect deve funzionare
 * anche senza JS: un `<meta http-equiv="refresh">` (hoistato nell'`<head>`
 * da Next) copre browser/crawler senza JS, mentre il link visibile è il
 * fallback finale per chi non segue il refresh automatico.
 * Il trailing slash (`/it/`) è coerente con `trailingSlash: true`.
 */
const target = `/${routing.defaultLocale}/`;

export default function RootPage() {
  return (
    <>
      <meta httpEquiv="refresh" content={`0;url=${target}`} />
      <main
        style={{
          minHeight: "100dvh",
          display: "grid",
          placeItems: "center",
          padding: "2rem",
          fontFamily: "system-ui, sans-serif",
          textAlign: "center",
        }}
      >
        <p>
          <Link href={target}>Vai al sito / Go to site</Link>
        </p>
      </main>
    </>
  );
}
