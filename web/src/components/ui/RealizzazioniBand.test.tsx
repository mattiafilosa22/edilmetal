import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { RealizzazioniBand } from "./RealizzazioniBand";

const progetto = {
  id: "1",
  slug: "aiassa",
  titolo: "Aiassa",
  cliente: "Aiassa Costruzioni",
  luogo: "Piacenza (PC)",
  anno: 2018,
  categoria: { slug: "strutture-miste" as const, nome: "Strutture miste" },
  inEvidenza: true,
  copertina: { src: "/a.jpg", width: 1200, height: 900, alt: "Aiassa" },
};

describe("RealizzazioniBand", () => {
  it("renders the section with the provided projects", () => {
    render(
      <NextIntlClientProvider locale="it" messages={{ Rail: { prev: "Precedente", next: "Successivo" } }}>
        <RealizzazioniBand
          progetti={[progetto]}
          locale="it"
          kick="In evidenza"
          title="Realizzazioni recenti."
          subtitle="Una selezione di commesse."
          ctaLabel="Tutte le realizzazioni"
          ctaHref="/it/realizzazioni"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByRole("heading", { name: "Realizzazioni recenti." })).toBeInTheDocument();
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
  });

  it("defaults the section number to 02 when num is not provided", () => {
    render(
      <NextIntlClientProvider locale="it" messages={{ Rail: { prev: "Precedente", next: "Successivo" } }}>
        <RealizzazioniBand
          progetti={[progetto]}
          locale="it"
          kick="In evidenza"
          title="Realizzazioni recenti."
          subtitle="Una selezione di commesse."
          ctaLabel="Tutte le realizzazioni"
          ctaHref="/it/realizzazioni"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("02")).toBeInTheDocument();
  });

  it("uses the num prop when provided", () => {
    render(
      <NextIntlClientProvider locale="it" messages={{ Rail: { prev: "Precedente", next: "Successivo" } }}>
        <RealizzazioniBand
          progetti={[progetto]}
          locale="it"
          num="05"
          kick="In evidenza"
          title="Realizzazioni recenti."
          subtitle="Una selezione di commesse."
          ctaLabel="Tutte le realizzazioni"
          ctaHref="/it/realizzazioni"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("05")).toBeInTheDocument();
  });

  it("renders nothing when there are no projects", () => {
    const { container } = render(
      <NextIntlClientProvider locale="it" messages={{}}>
        <RealizzazioniBand
          progetti={[]}
          locale="it"
          kick="In evidenza"
          title="Realizzazioni recenti."
          subtitle="Una selezione."
          ctaLabel="Tutte"
          ctaHref="/it/realizzazioni"
        />
      </NextIntlClientProvider>
    );
    expect(container).toBeEmptyDOMElement();
  });
});
