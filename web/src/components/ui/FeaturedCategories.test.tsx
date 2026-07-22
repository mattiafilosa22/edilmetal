import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { FeaturedCategories } from "./FeaturedCategories";
import messages from "@/i18n/messages/it.json";

const items = [
  {
    categoria: { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
    immagine: { src: "/a.jpg", width: 1200, height: 900, alt: "Capannone in acciaio" },
  },
  {
    categoria: { slug: "pensiline" as const, nome: "Pensiline" },
    immagine: { src: "/b.jpg", width: 1200, height: 900, alt: "Pensilina industriale" },
  },
];

function renderWithIntl(ui: React.ReactElement) {
  return render(
    <NextIntlClientProvider locale="it" messages={messages}>
      {ui}
    </NextIntlClientProvider>
  );
}

describe("FeaturedCategories", () => {
  it("renders one card per featured category with a link to the filtered listing", () => {
    renderWithIntl(<FeaturedCategories items={items} locale="it" />);
    expect(screen.getByText("Strutture in acciaio")).toBeInTheDocument();
    expect(screen.getByText("Pensiline")).toBeInTheDocument();
    const links = screen.getAllByRole("link", { name: /vai alla sezione prodotti/i });
    expect(links[0]).toHaveAttribute("href", "/it/realizzazioni?categoria=strutture-acciaio");
    expect(links[1]).toHaveAttribute("href", "/it/realizzazioni?categoria=pensiline");
  });

  it("renders nothing when there are no featured categories", () => {
    const { container } = renderWithIntl(<FeaturedCategories items={[]} locale="it" />);
    expect(container).toBeEmptyDOMElement();
  });
});
