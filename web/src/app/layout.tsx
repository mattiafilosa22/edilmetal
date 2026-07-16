import type { ReactNode } from "react";
import "./globals.css";

/**
 * Root layout minimale richiesto dallo static export senza middleware:
 * il markup `<html>`/`<body>` vive in `[locale]/layout.tsx`, così ogni
 * pagina può impostare `lang` in base alla locale. Qui passiamo solo i figli.
 */
export default function RootLayout({ children }: { children: ReactNode }) {
  return children;
}
