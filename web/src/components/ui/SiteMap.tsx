import { getTranslations } from "next-intl/server";
import { LeafletMount } from "./LeafletMount";

/**
 * Guscio server della mappa sede (RSC). Rende sempre — anche in export statico
 * e senza JavaScript — il contenitore e l'alternativa accessibile: un link
 * "Apri in mappe" con `aria-label` e coordinate. Lo strato interattivo Leaflet
 * (tile OpenStreetMap cookieless, marker locale) è un'isola client ssr:false.
 */

type SiteMapProps = {
  lat: number;
  lng: number;
  /** Etichetta descrittiva (indirizzo) per popup e aria-label. */
  label: string;
  /** Link esterno alle mappe; se assente si usa OpenStreetMap. */
  mapsUrl?: string;
  zoom?: number;
};

export async function SiteMap({
  lat,
  lng,
  label,
  mapsUrl,
  zoom = 15,
}: SiteMapProps) {
  const t = await getTranslations("Map");
  const href =
    mapsUrl ??
    `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=16/${lat}/${lng}`;

  return (
    <div className="map">
      <div className="map__canvas">
        <LeafletMount
          lat={lat}
          lng={lng}
          label={label}
          ariaLabel={t("aria", { label })}
          zoom={zoom}
        />
      </div>
      <a
        className="map__open btn btn--outline"
        href={href}
        target="_blank"
        rel="noopener noreferrer"
        aria-label={t("openAria", { label })}
      >
        <svg
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          aria-hidden="true"
        >
          <path d="M3 11l19-9-9 19-2-8-8-2z" />
        </svg>
        {t("open")}
      </a>
    </div>
  );
}
