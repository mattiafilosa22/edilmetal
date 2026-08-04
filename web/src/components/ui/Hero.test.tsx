import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import type { Image as ImageDto } from "@/domain";
import { Hero } from "./Hero";

const heroSliderMessages = {
  HeroSlider: {
    label: "Realizzazioni Edilmetal",
    prev: "Foto precedente",
    next: "Foto successiva",
    goTo: "Vai alla foto {n}",
    status: "Foto {current} di {total}",
  },
};

const hero = {
  title: "Strutture in acciaio",
  subtitle: "Progettazione, produzione e montaggio su commessa.",
  ctaPrimary: { label: "Le realizzazioni", href: "/realizzazioni" },
  index: [],
};

function renderPhotoOnly(heroImages: ImageDto[]) {
  return render(
    <NextIntlClientProvider locale="it" messages={heroSliderMessages}>
      <Hero hero={hero} locale="it" photoOnly heroImages={heroImages} />
    </NextIntlClientProvider>
  );
}

describe("Hero", () => {
  it("renders the real photo when heroImage is provided", () => {
    render(
      <Hero
        hero={hero}
        locale="it"
        heroImages={[{ src: "/mock/hero-parmalat.jpg", width: 1920, height: 1078, alt: "Gru in cantiere" }]}
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

  it("in photoOnly mode with a photo, renders only the image — no title, subtitle, CTA or index bar", () => {
    renderPhotoOnly([
      { src: "/mock/hero-parmalat.jpg", width: 1920, height: 1078, alt: "Gru in cantiere" },
    ]);
    expect(screen.getByAltText("Gru in cantiere")).toBeInTheDocument();
    expect(screen.queryByRole("heading", { level: 1 })).not.toBeInTheDocument();
    expect(screen.queryByText(hero.subtitle)).not.toBeInTheDocument();
    expect(screen.queryByText(hero.ctaPrimary.label)).not.toBeInTheDocument();
  });

  it("in photoOnly mode with more photos, renders the slider with its controls", () => {
    renderPhotoOnly([
      { src: "/mock/hero.jpg", width: 1920, height: 1078, alt: "Gru in cantiere" },
      { src: "/mock/bervini.jpg", width: 1600, height: 900, alt: "Struttura in acciaio" },
      { src: "/mock/acetum.jpg", width: 1600, height: 900, alt: "Capannone industriale" },
    ]);
    expect(screen.getByRole("button", { name: "Foto successiva" })).toBeInTheDocument();
    expect(screen.getAllByRole("button", { name: /Vai alla foto/ })).toHaveLength(3);
  });

  it("in photoOnly mode without a photo, still falls back to the blueprint drawing (never blank)", () => {
    renderPhotoOnly([]);
    expect(screen.getByLabelText("Schema tecnico di un telaio in acciaio con capriata")).toBeInTheDocument();
  });
});
