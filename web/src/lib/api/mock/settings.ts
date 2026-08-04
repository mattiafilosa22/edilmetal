import type { SiteSettings } from "@/domain";

/**
 * Impostazioni demo. Dati sede reali (indirizzo, telefono, fax, orari,
 * coordinate) e foto hero reale (archivio storico cantiere Parmalat);
 * email, P.IVA, ragione sociale e social restano segnaposto finché non
 * vengono forniti/gestiti in WordPress.
 */
export const mockSettings: SiteSettings = {
  nomeAzienda: "Edilmetal",
  ragioneSociale: "Edilmetal S.r.l.",
  partitaIva: "00000000000",
  indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
  telefono: "0521 615023",
  fax: "0521 615207",
  email: "edilmetal@edilmetal.it",
  // Coordinate indicative di Noceto (PR), zona Ponte Taro.
  coordinate: { lat: 44.8103, lng: 10.1747 },
  mapsUrl:
    "https://www.google.com/maps/search/?api=1&query=Piazza+Alpini+d%27Italia+10%2FA+Noceto+PR",
  orari: [{ giorni: "Lun–Ven", apertura: "08:00–12:00 / 14:00–18:00" }],
  social: {
    linkedin: "#",
    facebook: "#",
    instagram: "#",
  },
  heroImage: {
    src: "/mock/hero-parmalat.jpg",
    width: 1920,
    height: 1078,
    alt: "Gru che monta la struttura in acciaio di un capannone Parmalat",
  },
  fotoCredit: "Archivio fotografico Edilmetal.",
};
