import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import Link from "next/link";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { Rail } from "./Rail";

const messages = { Rail: { prev: "Precedente", next: "Successivo" } };

/**
 * `is-grabbing` attiva `pointer-events:none` sulle card (CSS): se scattasse
 * già al pointerdown, un click senza trascinamento perderebbe il target al
 * mouseup e il link non navigherebbe mai (bug riprodotto in produzione).
 */
describe("Rail", () => {
  it("does not mark the rail as grabbing on a plain click (no pointer movement)", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <Rail>
          <Link className="proj" href="/it/realizzazioni/aiassa">
            Aiassa
          </Link>
        </Rail>
      </NextIntlClientProvider>
    );

    const link = screen.getByRole("link", { name: "Aiassa" });
    const rail = link.parentElement as HTMLElement;

    fireEvent.pointerDown(link, { clientX: 100, pointerType: "mouse" });
    expect(rail).not.toHaveClass("is-grabbing");

    fireEvent.pointerUp(window, { clientX: 100 });
    expect(rail).not.toHaveClass("is-grabbing");
  });

  it("marks the rail as grabbing only once the pointer actually drags", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <Rail>
          <Link className="proj" href="/it/realizzazioni/aiassa">
            Aiassa
          </Link>
        </Rail>
      </NextIntlClientProvider>
    );

    const link = screen.getByRole("link", { name: "Aiassa" });
    const rail = link.parentElement as HTMLElement;

    fireEvent.pointerDown(link, { clientX: 100, pointerType: "mouse" });
    fireEvent.pointerMove(window, { clientX: 40 });
    expect(rail).toHaveClass("is-grabbing");

    fireEvent.pointerUp(window, { clientX: 40 });
    expect(rail).not.toHaveClass("is-grabbing");
  });
});
