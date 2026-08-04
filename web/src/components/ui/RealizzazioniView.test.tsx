import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { fireEvent } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { RealizzazioniView } from "./RealizzazioniView";
import messages from "@/i18n/messages/it.json";

const summaries = [
  {
    id: "1",
    slug: "aiassa",
    titolo: "Aiassa",
    cliente: "Aiassa",
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
const categorie = [
  { slug: "strutture-miste" as const, nome: "Strutture miste" },
  { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
];

describe("RealizzazioniView", () => {
  it("pre-filters by the given initialCategoria", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView
          summaries={summaries}
          categorie={categorie}
          locale="it"
          initialCategoria="strutture-miste"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.queryByText("Acetum")).not.toBeInTheDocument();
  });

  it("shows every project when no category is selected", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView summaries={summaries} categorie={categorie} locale="it" />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.getByText("Acetum")).toBeInTheDocument();
  });

  it("does not paginate when there are 9 or fewer projects", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView summaries={summaries} categorie={categorie} locale="it" />
      </NextIntlClientProvider>
    );
    expect(screen.queryByRole("navigation", { name: "Paginazione" })).not.toBeInTheDocument();
  });

  it("paginates 9 projects per page and lets you navigate to the next page", () => {
    // Titoli con zero-padding: la vista ordina alfabeticamente (stesso anno),
    // "Progetto 10" precederebbe "Progetto 2" con un ordinamento naturale.
    const many = Array.from({ length: 10 }, (_, i) => ({
      ...summaries[0],
      id: String(i + 1),
      slug: `progetto-${i + 1}`,
      titolo: `Progetto ${String(i + 1).padStart(2, "0")}`,
      cliente: `Progetto ${String(i + 1).padStart(2, "0")}`,
    }));

    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView summaries={many} categorie={categorie} locale="it" />
      </NextIntlClientProvider>
    );

    expect(screen.getByText("Progetto 01")).toBeInTheDocument();
    expect(screen.getByText("Progetto 09")).toBeInTheDocument();
    expect(screen.queryByText("Progetto 10")).not.toBeInTheDocument();
    expect(screen.getByText("Pagina 1 di 2")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Vai a pagina 2" }));

    expect(screen.getByText("Progetto 10")).toBeInTheDocument();
    expect(screen.queryByText("Progetto 01")).not.toBeInTheDocument();
    expect(screen.getByText("Pagina 2 di 2")).toBeInTheDocument();
  });

  it("resets to page 1 when the category filter changes", () => {
    const many = Array.from({ length: 10 }, (_, i) => ({
      ...summaries[0],
      id: String(i + 1),
      slug: `progetto-${i + 1}`,
      titolo: `Progetto ${String(i + 1).padStart(2, "0")}`,
      cliente: `Progetto ${String(i + 1).padStart(2, "0")}`,
      categoria: i === 0 ? summaries[1].categoria : summaries[0].categoria,
    }));

    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView summaries={many} categorie={categorie} locale="it" />
      </NextIntlClientProvider>
    );

    fireEvent.click(screen.getByRole("button", { name: "Vai a pagina 2" }));
    expect(screen.getByText("Pagina 2 di 2")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Strutture in acciaio" }));
    expect(screen.getByText("Progetto 01")).toBeInTheDocument();
    expect(screen.queryByRole("navigation", { name: "Paginazione" })).not.toBeInTheDocument();
  });
});
