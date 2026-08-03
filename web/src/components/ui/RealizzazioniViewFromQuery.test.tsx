import "@testing-library/jest-dom/vitest";
import { cleanup, render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { afterEach, describe, expect, it, vi } from "vitest";
import messages from "@/i18n/messages/it.json";

// Più test nello stesso file: senza cleanup esplicito il DOM renderizzato
// da un test resterebbe montato per il successivo (jsdom non lo fa da solo
// in questo setup Vitest).
afterEach(cleanup);

const useSearchParams = vi.fn();

vi.mock("next/navigation", () => ({
  useSearchParams: () => useSearchParams(),
}));

// L'import va fatto dopo il mock di next/navigation.
import { RealizzazioniViewFromQuery } from "./RealizzazioniViewFromQuery";

const summaries = [
  {
    id: "1",
    slug: "aiassa",
    titolo: "Aiassa",
    cliente: "Aiassa Costruzioni",
    luogo: "Piacenza (PC)",
    anno: 2018,
    categoria: { slug: "strutture-miste" as const, nome: "Strutture miste" },
    inEvidenza: false,
    copertina: { src: "/a.jpg", width: 1200, height: 900, alt: "Aiassa" },
  },
  {
    id: "2",
    slug: "acetum",
    titolo: "Acetum",
    cliente: "Acetum S.p.A.",
    luogo: "Parma (PR)",
    anno: 2018,
    categoria: { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
    inEvidenza: false,
    copertina: { src: "/b.jpg", width: 1200, height: 900, alt: "Acetum" },
  },
];

const categorie = [
  { slug: "strutture-miste" as const, nome: "Strutture miste" },
  { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
];

function renderFromQuery() {
  render(
    <NextIntlClientProvider locale="it" messages={messages}>
      <RealizzazioniViewFromQuery
        summaries={summaries}
        categorie={categorie}
        locale="it"
      />
    </NextIntlClientProvider>
  );
}

describe("RealizzazioniViewFromQuery", () => {
  it("inoltra una categoria valida dalla query string come initialCategoria", () => {
    useSearchParams.mockReturnValue(new URLSearchParams("categoria=strutture-miste"));

    renderFromQuery();

    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.queryByText("Acetum")).not.toBeInTheDocument();
  });

  it("ignora una categoria non valida in query string (fallback sicuro, nessun filtro)", () => {
    useSearchParams.mockReturnValue(new URLSearchParams("categoria=non-esiste"));

    renderFromQuery();

    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.getByText("Acetum")).toBeInTheDocument();
  });

  it("nessun crash e nessun filtro applicato quando `categoria` è assente dalla query string", () => {
    useSearchParams.mockReturnValue(new URLSearchParams(""));

    renderFromQuery();

    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.getByText("Acetum")).toBeInTheDocument();
  });
});
