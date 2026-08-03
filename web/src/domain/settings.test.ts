import { describe, expect, it } from "vitest";
import { siteSettingsSchema } from "./settings";

const base = {
  nomeAzienda: "Edilmetal",
  ragioneSociale: "Edilmetal S.r.l.",
  partitaIva: "01234567890",
  indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
  telefono: "0521 615023",
  email: "info@edilmetal.it",
  coordinate: { lat: 44.8103, lng: 10.1747 },
  orari: [],
  social: {},
};

describe("siteSettingsSchema", () => {
  it("accepts heroImage, fotoCredit and fax when present", () => {
    const parsed = siteSettingsSchema.parse({
      ...base,
      fax: "0521 615207",
      fotoCredit: "Archivio fotografico Edilmetal",
      heroImage: {
        src: "https://cms.edilmetal.it/wp-content/uploads/parmalat-1.jpg",
        width: 4160,
        height: 2336,
        alt: "Montaggio di una struttura in acciaio per Parmalat",
      },
    });
    expect(parsed.fax).toBe("0521 615207");
    expect(parsed.heroImage?.width).toBe(4160);
  });

  it("parses fine when heroImage/fotoCredit/fax are absent", () => {
    const parsed = siteSettingsSchema.parse(base);
    expect(parsed.heroImage).toBeUndefined();
    expect(parsed.fax).toBeUndefined();
  });
});
