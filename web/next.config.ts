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

// Quando il build interroga l'API WordPress reale (hosting CloudLinux con limiti
// di entry-process → HTTP 508), riduciamo i worker di prerender per non saturare
// il backend. Il build da mock (senza WP_API_URL) resta pienamente parallelo.
if (process.env.WP_API_URL) {
  const cpus = Number(process.env.BUILD_CPUS ?? "2");
  (nextConfig as { experimental?: Record<string, unknown> }).experimental = {
    ...((nextConfig as { experimental?: Record<string, unknown> }).experimental ??
      {}),
    cpus,
  };
}

export default withNextIntl(nextConfig);
