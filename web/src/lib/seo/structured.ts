import type { Progetto, SiteSettings } from "@/domain";
import type { JsonInput, JsonObject } from "./jsonLd";
import { schemaNode } from "./jsonLd";

/**
 * Builder dei nodi schema.org a partire dai DATI REALI (settings/progetto).
 * Nessun campo inventato: ciò che manca viene omesso (grazie a `pruneJson`).
 * Dominio B2B carpenteria: `Organization`/`LocalBusiness` + `CreativeWork`
 * per le realizzazioni. Nessun prezzo.
 */

/** Giorni IT (abbreviati) → DayOfWeek schema.org. */
const DAY_MAP: Record<string, string> = {
  lun: "Monday",
  mar: "Tuesday",
  mer: "Wednesday",
  gio: "Thursday",
  ven: "Friday",
  sab: "Saturday",
  dom: "Sunday",
};
const DAY_ORDER = [
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
  "Sunday",
];

export type ParsedAddress = {
  streetAddress?: string;
  postalCode?: string;
  addressLocality?: string;
  addressRegion?: string;
  addressCountry: string;
};

/**
 * Estrae i componenti dell'indirizzo dalla stringa unica dei settings
 * (es. "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)"). Ogni componente non
 * ricavabile viene omesso; il paese IT è l'unico valore costante ragionevole.
 */
export function parseAddress(indirizzo: string): ParsedAddress {
  const postalCode = indirizzo.match(/\b(\d{5})\b/)?.[1];
  const addressRegion = indirizzo.match(/\(([A-Za-z]{2})\)/)?.[1]?.toUpperCase();
  const streetAddress = indirizzo.split(",")[0]?.trim() || undefined;

  let addressLocality: string | undefined;
  if (postalCode) {
    const afterCap = indirizzo.slice(
      indirizzo.indexOf(postalCode) + postalCode.length
    );
    addressLocality = afterCap.replace(/\([^)]*\)/g, "").trim() || undefined;
  }

  return {
    streetAddress,
    postalCode,
    addressLocality,
    addressRegion,
    addressCountry: "IT",
  };
}

function postalAddressNode(indirizzo: string): JsonInput {
  const parsed = parseAddress(indirizzo);
  return {
    "@type": "PostalAddress",
    streetAddress: parsed.streetAddress,
    postalCode: parsed.postalCode,
    addressLocality: parsed.addressLocality,
    addressRegion: parsed.addressRegion,
    addressCountry: parsed.addressCountry,
  };
}

/** "Lun–Ven" / "Sab" → elenco di DayOfWeek schema.org. */
function parseDays(giorni: string): string[] {
  const tokens = giorni
    .toLowerCase()
    .split(/[–\-—]/)
    .map((t) => t.trim().slice(0, 3))
    .map((t) => DAY_MAP[t])
    .filter((d): d is string => Boolean(d));

  if (tokens.length === 0) return [];
  if (tokens.length === 1) return tokens;

  const start = DAY_ORDER.indexOf(tokens[0]);
  const end = DAY_ORDER.indexOf(tokens[tokens.length - 1]);
  if (start === -1 || end === -1 || end < start) return tokens;
  return DAY_ORDER.slice(start, end + 1);
}

/** "08:00–12:00 / 14:00–18:00" → coppie {opens, closes} normalizzate HH:MM. */
function parseShifts(apertura: string): Array<{ opens: string; closes: string }> {
  if (/chius/i.test(apertura)) return [];
  const shifts: Array<{ opens: string; closes: string }> = [];
  for (const chunk of apertura.split("/")) {
    const times = chunk.match(/\d{1,2}[:.]\d{2}/g);
    if (times && times.length >= 2) {
      shifts.push({
        opens: times[0].replace(".", ":"),
        closes: times[1].replace(".", ":"),
      });
    }
  }
  return shifts;
}

/** Costruisce le `OpeningHoursSpecification` dagli orari editabili in WP. */
export function buildOpeningHours(orari: SiteSettings["orari"]): JsonInput[] {
  const specs: JsonInput[] = [];
  for (const { giorni, apertura } of orari) {
    const dayOfWeek = parseDays(giorni);
    const shifts = parseShifts(apertura);
    if (dayOfWeek.length === 0 || shifts.length === 0) continue;
    for (const shift of shifts) {
      specs.push({
        "@type": "OpeningHoursSpecification",
        dayOfWeek,
        opens: shift.opens,
        closes: shift.closes,
      });
    }
  }
  return specs;
}

/** Link social validi (esclude segnaposto "#"/vuoti) per `sameAs`. */
export function socialSameAs(settings: SiteSettings): string[] {
  return Object.values(settings.social)
    .filter((url): url is string => typeof url === "string")
    .map((url) => url.trim())
    .filter((url) => url.length > 0 && url !== "#" && /^https?:\/\//.test(url));
}

export type ProjectJsonLdInput = {
  progetto: Progetto;
  name: string;
  url: string;
  images: string[];
  /** Organizzazione autrice dell'opera (Edilmetal). */
  creatorName: string;
  creatorUrl: string;
};

/** Nodo `CreativeWork` per una realizzazione (case study). */
export function buildProjectJsonLd(input: ProjectJsonLdInput): JsonObject {
  const { progetto, name, url, images, creatorName, creatorUrl } = input;
  const parsed = parseAddress(progetto.luogo);
  return schemaNode("CreativeWork", {
    name,
    url,
    image: images,
    description: progetto.descrizione,
    dateCreated: String(progetto.anno),
    about: progetto.categoria.nome,
    keywords: [progetto.categoria.nome, progetto.settore].filter(Boolean),
    creator: {
      "@type": "Organization",
      name: creatorName,
      url: creatorUrl,
    },
    contentLocation: {
      "@type": "Place",
      name: progetto.luogo,
      address: {
        "@type": "PostalAddress",
        addressLocality: parsed.addressLocality ?? progetto.luogo,
        addressRegion: parsed.addressRegion,
        addressCountry: "IT",
      },
    },
  });
}

export type OrganizationJsonLdInput = {
  settings: SiteSettings;
  url: string;
  logoUrl: string;
  /** `LocalBusiness` per la scheda "dove siamo", `Organization` altrove. */
  type?: "Organization" | "LocalBusiness";
  /** Aggiunge geo/orari/areaServed (scheda sede). */
  withPlaceData?: boolean;
};

/** Nodo `Organization`/`LocalBusiness` dai settings reali (Noceto, PR). */
export function buildOrganizationJsonLd(
  input: OrganizationJsonLdInput
): JsonObject {
  const {
    settings,
    url,
    logoUrl,
    type = "Organization",
    withPlaceData = false,
  } = input;

  const sameAs = socialSameAs(settings);
  const openingHours = withPlaceData
    ? buildOpeningHours(settings.orari)
    : undefined;
  const geo = withPlaceData
    ? {
        "@type": "GeoCoordinates",
        latitude: settings.coordinate.lat,
        longitude: settings.coordinate.lng,
      }
    : undefined;

  return schemaNode(type, {
    name: settings.nomeAzienda,
    legalName: settings.ragioneSociale,
    url,
    logo: logoUrl,
    image: logoUrl,
    telephone: settings.telefono,
    email: settings.email,
    vatID: settings.partitaIva,
    address: postalAddressNode(settings.indirizzo),
    geo,
    hasMap: settings.mapsUrl,
    areaServed: withPlaceData ? undefined : "Italia",
    openingHoursSpecification: openingHours,
    sameAs,
  });
}

export type Crumb = { name: string; url: string };

/** Nodo `BreadcrumbList` con posizioni 1..n e URL assoluti. */
export function buildBreadcrumbJsonLd(items: Crumb[]): JsonObject {
  return schemaNode("BreadcrumbList", {
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.name,
      item: item.url,
    })),
  });
}
