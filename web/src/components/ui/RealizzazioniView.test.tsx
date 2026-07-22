import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { RealizzazioniView } from "./RealizzazioniView";
import messages from "@/i18n/messages/it.json";

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
    cliente: "Acetum",
    luogo: "Parma (PR)",
    anno: 2018,
    categoria: { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
    inEvidenza: false,
    copertina: { src: "/b.jpg", width: 1200, height: 900, alt: "Acetum" },
  },
];

describe("RealizzazioniView", () => {
  it("pre-filters by the given initialCategoria", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView
          summaries={summaries}
          categorie={[
            { slug: "strutture-miste", nome: "Strutture miste" },
            { slug: "strutture-acciaio", nome: "Strutture in acciaio" },
          ]}
          settori={[]}
          anni={[2018]}
          locale="it"
          initialCategoria="strutture-miste"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.queryByText("Acetum")).not.toBeInTheDocument();
  });
});
