"use client";

import "leaflet/dist/leaflet.css";
import { MapContainer, Marker, Popup, TileLayer } from "react-leaflet";
import { icon } from "leaflet";

/**
 * Vista mappa Leaflet effettiva. Caricata SOLO lato client via
 * `dynamic(() => import(...), { ssr: false })`: Leaflet accede a `window`,
 * quindi non deve mai eseguire nel render server / export statico.
 *
 * Le tile sono OpenStreetMap (gratuite, cookieless) con attribuzione OSM
 * visibile e obbligatoria. Le icone marker sono asset locali in `public/leaflet`
 * per non dipendere dai CDN di default di Leaflet (che romperebbero in export).
 */

const markerIcon = icon({
  iconUrl: "/leaflet/marker-icon.png",
  iconRetinaUrl: "/leaflet/marker-icon-2x.png",
  shadowUrl: "/leaflet/marker-shadow.png",
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
});

type SiteMapViewProps = {
  lat: number;
  lng: number;
  label: string;
  ariaLabel: string;
  zoom: number;
};

export default function SiteMapView({
  lat,
  lng,
  label,
  ariaLabel,
  zoom,
}: SiteMapViewProps) {
  return (
    <MapContainer
      center={[lat, lng]}
      zoom={zoom}
      scrollWheelZoom={false}
      aria-label={ariaLabel}
      style={{ height: "100%", width: "100%" }}
    >
      <TileLayer
        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
        maxZoom={19}
      />
      <Marker position={[lat, lng]} icon={markerIcon}>
        <Popup>{label}</Popup>
      </Marker>
    </MapContainer>
  );
}
