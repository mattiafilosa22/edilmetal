import "@testing-library/jest-dom/vitest";
import { render, screen, within } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import type { Progetto, SiteSettings } from "@/domain";
import SchedaProgettoPage from "./page";

const { progetto, settings } = vi.hoisted(() => ({
  progetto: {
    id: "1",
    slug: "pensilina-industriale",
    titolo: "Pensilina industriale",
    cliente: "Cliente riservato",
    luogo: "Noceto (PR)",
    anno: 2025,
    categoria: { slug: "pensiline", nome: "Pensiline" },
    settore: "Industriale",
    inEvidenza: true,
    descrizione: "<p>Descrizione tecnica unica del progetto.</p>",
    galleria: [
      {
        src: "/pensilina.jpg",
        width: 1200,
        height: 800,
        alt: "Pensilina industriale in acciaio",
      },
    ],
    datiTecnici: [{ label: "Luce", valore: "24 m" }],
    lavorazioni: ["Montaggio in cantiere"],
    materiali: ["Acciaio S275JR"],
    seo: {
      title: "Pensilina industriale",
      description: "Case study di una pensilina industriale.",
    },
  } satisfies Progetto,
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
  getProgetto: vi.fn().mockResolvedValue(progetto),
  getProgetti: vi.fn().mockResolvedValue([]),
  getSettings: vi.fn().mockResolvedValue(settings),
}));

vi.mock("next-intl/server", () => ({
  getTranslations: vi.fn().mockResolvedValue((key: string) => key),
  setRequestLocale: vi.fn(),
}));

vi.mock("next-intl", () => ({
  useTranslations: () => (key: string) => key,
}));

describe("SchedaProgettoPage", () => {
  it("keeps the project description in its tab and removes it from the page head", async () => {
    const { container } = render(
      await SchedaProgettoPage({
        params: Promise.resolve({ locale: "it", slug: progetto.slug }),
      })
    );
    const pageHead = container.querySelector(".page-head");

    if (!(pageHead instanceof HTMLElement)) {
      throw new Error("Expected the project page head to be rendered");
    }
    expect(within(pageHead).queryByText("Descrizione tecnica unica del progetto.")).not.toBeInTheDocument();
    expect(
      within(screen.getByRole("tabpanel", { name: "tabDescrizione" })).getByText(
        "Descrizione tecnica unica del progetto."
      )
    ).toBeInTheDocument();
  });
});
