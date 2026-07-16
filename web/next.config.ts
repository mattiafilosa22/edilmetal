import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin("./src/i18n/request.ts");

// Sottocartella opzionale (es. anteprima protetta in /anteprima su Plesk):
// impostare SITE_BASE_PATH="/anteprima" a build time. Vuoto = deploy in root.
const basePath = (process.env.SITE_BASE_PATH ?? "").replace(/\/$/, "");

const nextConfig: NextConfig = {
  // Static export: nessun runtime Node in produzione (hosting statico su Plesk).
  output: "export",
  trailingSlash: true,
  ...(basePath ? { basePath, assetPrefix: basePath } : {}),
  // Fissa la root del workspace su `web/`: evita il warning "multiple lockfiles"
  // quando esistono più package-lock.json risalendo l'albero delle cartelle.
  turbopack: {
    root: __dirname,
  },
  outputFileTracingRoot: __dirname,
  images: {
    // L'ottimizzatore server di Next non è disponibile in export statico.
    // Le foto arrivano già dimensionate dalle image-sizes di WordPress.
    unoptimized: true,
  },
  reactStrictMode: true,
};

export default withNextIntl(nextConfig);
