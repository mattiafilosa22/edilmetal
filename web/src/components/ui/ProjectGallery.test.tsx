import "@testing-library/jest-dom/vitest";
import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { afterEach, describe, expect, it } from "vitest";
import { ProjectGallery } from "./ProjectGallery";
import messages from "@/i18n/messages/it.json";

afterEach(cleanup);

function renderGallery(images: Parameters<typeof ProjectGallery>[0]["images"]) {
  render(
    <NextIntlClientProvider locale="it" messages={messages}>
      <ProjectGallery images={images} fallbackAlt="Scale" />
    </NextIntlClientProvider>
  );
}

describe("ProjectGallery", () => {
  it("shows a placeholder with the fallback alt when the gallery is empty", () => {
    renderGallery([]);
    const img = screen.getByRole("img", { name: "Scale" });
    expect(img.getAttribute("src")).toContain("placeholder-progetto.svg");
    expect(screen.queryByRole("group", { name: "Miniature della galleria" })).not.toBeInTheDocument();
  });

  it("shows thumbnails only with more than one photo", () => {
    renderGallery([
      { src: "/1.jpg", width: 1200, height: 800, alt: "Foto 1" },
      { src: "/2.jpg", width: 1200, height: 800, alt: "Foto 2" },
    ]);
    expect(screen.getByRole("img", { name: "Foto 1" })).toBeInTheDocument();
    expect(screen.getByRole("group", { name: "Miniature della galleria" })).toBeInTheDocument();
  });

  it("uses the first image initially and follows thumbnail order", () => {
    renderGallery([
      { src: "/finale.jpg", width: 1200, height: 800, alt: "Risultato finale" },
      { src: "/cantiere.jpg", width: 1200, height: 800, alt: "Cantiere" },
    ]);
    expect(screen.getByRole("img", { name: "Risultato finale" })).toHaveAttribute("src", expect.stringContaining("finale.jpg"));
    fireEvent.click(screen.getByRole("button", { name: "Cantiere" }));
    expect(screen.getByRole("img", { name: "Cantiere" })).toHaveAttribute("src", expect.stringContaining("cantiere.jpg"));
  });
});
