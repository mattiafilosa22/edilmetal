import "@testing-library/jest-dom/vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { beforeEach, describe, expect, it } from "vitest";
import { SiteNotice } from "./SiteNotice";

const messages = {
  SiteNotice: {
    title: "Stiamo lavorando per rendere il sito ancora più fruibile.",
    text: "Sito in aggiornamento.",
    cta: "Ho capito",
    close: "Chiudi l'avviso",
  },
};

function renderNotice() {
  return render(
    <NextIntlClientProvider locale="it" messages={messages}>
      <SiteNotice />
    </NextIntlClientProvider>
  );
}

describe("SiteNotice", () => {
  beforeEach(() => {
    window.sessionStorage.clear();
  });

  it("shows the notice on the first visit of the session", () => {
    renderNotice();
    expect(screen.getByText("Stiamo lavorando per rendere il sito ancora più fruibile.")).toBeInTheDocument();
    expect(screen.getByText("Sito in aggiornamento.")).toBeInTheDocument();
    expect(screen.queryByText("00")).not.toBeInTheDocument();
    expect(screen.queryByText("Avviso")).not.toBeInTheDocument();
  });

  it("can be dismissed and does not come back within the same session", () => {
    const { unmount } = renderNotice();
    fireEvent.click(screen.getByRole("button", { name: "Ho capito" }));
    expect(screen.queryByText("Stiamo lavorando per rendere il sito ancora più fruibile.")).not.toBeInTheDocument();

    unmount();
    renderNotice();
    expect(screen.queryByText("Stiamo lavorando per rendere il sito ancora più fruibile.")).not.toBeInTheDocument();
  });

  it("can also be dismissed with the close button", () => {
    renderNotice();
    fireEvent.click(screen.getByRole("button", { name: "Chiudi l'avviso" }));
    expect(screen.queryByText("Stiamo lavorando per rendere il sito ancora più fruibile.")).not.toBeInTheDocument();
  });
});
