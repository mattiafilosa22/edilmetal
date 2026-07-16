"use client";

/**
 * Script inline anti-flash: imposta `data-theme` sull'`<html>` PRIMA
 * dell'idratazione, leggendo localStorage e, in mancanza, `prefers-color-scheme`.
 *
 * Il `type` è dinamico (pattern ufficiale Next.js, guida "preventing flash
 * before hydration"):
 * - lato SERVER (`typeof window === "undefined"`) → `text/javascript`: lo
 *   snippet è nell'HTML iniziale ed esegue sincronamente prima del primo paint;
 * - lato CLIENT → `text/plain`: quando il reconciler ricrea lo `<script>`
 *   (es. navigazione cross-locale che ri-renderizza il layout `[locale]`) lo
 *   tratta come dato inerte, così React NON emette il warning "Encountered a
 *   script tag…" e il browser non riesegue inutilmente lo script.
 * `suppressHydrationWarning` copre la differenza di `type` durante l'idratazione.
 * Serve `"use client"` affinché il branch `typeof window` sia valutato anche nel
 * browser (un Server Component verrebbe serializzato sempre con `text/javascript`).
 */
const script = `(function(){try{var t=localStorage.getItem('theme');if(t!=='light'&&t!=='dark'){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','light');}})();`;

export function ThemeScript() {
  return (
    <script
      type={typeof window === "undefined" ? "text/javascript" : "text/plain"}
      suppressHydrationWarning
      dangerouslySetInnerHTML={{ __html: script }}
    />
  );
}
