import type { MetadataRoute } from "next";
import { SITE_URL, absoluteUrl } from "@/lib/seo";

/** Richiesto da `output: 'export'`: emette `out/robots.txt` staticamente. */
export const dynamic = "force-static";

/**
 * robots.txt statico generato all'export (`out/robots.txt`).
 * Consente l'intero sito (vetrina pubblica) e dichiara la sitemap assoluta.
 */
export default function robots(): MetadataRoute.Robots {
  return {
    rules: [{ userAgent: "*", allow: "/" }],
    sitemap: absoluteUrl("/sitemap.xml"),
    host: SITE_URL,
  };
}
