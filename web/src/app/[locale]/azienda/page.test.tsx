import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import type { PageContent, SiteSettings } from "@/domain";
import AziendaPage from "./page";

const { aziendaPage, settings } = vi.hoisted(() => ({
  aziendaPage: {
    key: "azienda",
    title: "Edilmetal dal 1997",
    subtitle: "Carpenteria metallica su commessa.",
    azienda: {
      storiaTitolo: "La società",
      storia: [
        "Edilmetal nasce dall'esperienza di Alessio Ricci e Aldo Medioli.",
        "Ogni commessa segue progettazione, produzione e montaggio.",
      ],
    },
  } satisfies PageContent,
  settings: {
    nomeAzienda: "Edilmetal",
    ragioneSociale: "Edilmetal S.r.l.",
    partitaIva: "00000000000",
    indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
    telefono: "0521 615023",
    email: "info@edilmetal.it",
    coordinate: { lat: 44.8103, lng: 10.1747 },
    orari: [{ giorni: "Lun–Ven", apertura: "08:00–12:00 / 14:00–18:00" }],
    social: {},
  } satisfies SiteSettings,
}));

vi.mock("@/lib/api", () => ({
  getPage: vi.fn().mockResolvedValue(aziendaPage),
  getSettings: vi.fn().mockResolvedValue(settings),
}));

vi.mock("next-intl/server", () => ({
  getTranslations: vi.fn().mockResolvedValue((key: string) => key),
  setRequestLocale: vi.fn(),
}));

describe("AziendaPage", () => {
  it("shows the company history without rendering its redundant heading", async () => {
    render(await AziendaPage({ params: Promise.resolve({ locale: "it" }) }));

    for (const paragraph of aziendaPage.azienda.storia) {
      expect(screen.getByText(paragraph)).toBeInTheDocument();
    }
    expect(screen.queryByText("La società")).not.toBeInTheDocument();
  });
});
