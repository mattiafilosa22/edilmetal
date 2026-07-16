import type { NextConfig } from "next";
import createNextIntlPlugin from "next-intl/plugin";

const withNextIntl = createNextIntlPlugin("./src/i18n/request.ts");

const nextConfig: NextConfig = {
  // Static export: nessun runtime Node in produzione (hosting VHosting solo-PHP).
  output: "export",
  trailingSlash: true,
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
