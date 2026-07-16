"use client";

import dynamic from "next/dynamic";

/**
 * Isola client-side lo strato interattivo Leaflet. `dynamic(..., ssr:false)`
 * evita che Leaflet (che usa `window`) esegua nel render server / export
 * statico. Il guscio accessibile e il link "Apri in mappe" restano lato server
 * in `SiteMap`, così esistono anche senza JavaScript.
 */

type LeafletMountProps = {
  lat: number;
  lng: number;
  label: string;
  ariaLabel: string;
  zoom: number;
};

const SiteMapView = dynamic(() => import("./SiteMapView"), {
  ssr: false,
  loading: MapPlaceholder,
});

function MapPlaceholder() {
  return (
    <div className="map__placeholder" aria-hidden="true">
      <svg
        width="40"
        height="40"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.5"
      >
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
        <circle cx="12" cy="10" r="3" />
      </svg>
    </div>
  );
}

export function LeafletMount(props: LeafletMountProps) {
  return <SiteMapView {...props} />;
}
