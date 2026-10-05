import "@testing-library/jest-dom/vitest";
import { cleanup, render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { afterEach, describe, expect, it } from "vitest";
import { ProjectTabs } from "./ProjectTabs";
import messages from "@/i18n/messages/it.json";

afterEach(cleanup);

describe("ProjectTabs", () => {
  it("renders nothing when there is no technical content", () => {
    const { container } = render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ProjectTabs datiTecnici={[]} lavorazioni={[]} materiali={[]} />
      </NextIntlClientProvider>
    );
    expect(container).toBeEmptyDOMElement();
    expect(screen.queryByRole("tablist")).not.toBeInTheDocument();
  });

  it("with a single content renders heading + panel, without a tablist", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ProjectTabs
          datiTecnici={[{ label: "Superficie", valore: "600 m²" }]}
          lavorazioni={[]}
          materiali={[]}
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByRole("heading", { level: 2, name: "Dati tecnici" })).toBeInTheDocument();
    expect(screen.getByText("600 m²")).toBeInTheDocument();
    expect(screen.getByRole("region", { name: "Dati tecnici" })).toBeInTheDocument();
    expect(screen.queryByRole("tablist")).not.toBeInTheDocument();
    expect(screen.queryByRole("tab")).not.toBeInTheDocument();
  });

  it("with more contents shows the tabs, without a description tab", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ProjectTabs
          datiTecnici={[{ label: "Superficie", valore: "600 m²" }]}
          lavorazioni={["Montaggio"]}
          materiali={[]}
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByRole("tablist")).toBeInTheDocument();
    expect(screen.getByRole("tab", { name: "Dati tecnici" })).toHaveAttribute("aria-selected", "true");
    expect(screen.getByRole("tab", { name: "Lavorazioni" })).toBeInTheDocument();
    expect(screen.queryByRole("tab", { name: "Descrizione" })).not.toBeInTheDocument();
  });
});
