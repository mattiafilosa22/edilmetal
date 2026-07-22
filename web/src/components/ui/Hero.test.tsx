import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Hero } from "./Hero";

const hero = {
  title: "Strutture in acciaio",
  subtitle: "Progettazione, produzione e montaggio su commessa.",
  ctaPrimary: { label: "Le realizzazioni", href: "/realizzazioni" },
  index: [],
};

describe("Hero", () => {
  it("renders the real photo when heroImage is provided", () => {
    render(
      <Hero
        hero={hero}
        locale="it"
        heroImage={{ src: "/mock/hero-parmalat.jpg", width: 1920, height: 1078, alt: "Gru in cantiere" }}
        fotoCredit="Archivio Edilmetal"
      />
    );
    expect(screen.getByAltText("Gru in cantiere")).toBeInTheDocument();
    expect(screen.getByText("Archivio Edilmetal")).toBeInTheDocument();
    expect(screen.queryByLabelText("Schema tecnico di un telaio in acciaio con capriata")).not.toBeInTheDocument();
  });

  it("falls back to the blueprint drawing when heroImage is absent", () => {
    render(<Hero hero={hero} locale="it" />);
    expect(screen.getByLabelText("Schema tecnico di un telaio in acciaio con capriata")).toBeInTheDocument();
  });
});
