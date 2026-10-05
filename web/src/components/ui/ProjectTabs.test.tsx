import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { ProjectTabs } from "./ProjectTabs";
import messages from "@/i18n/messages/it.json";

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

  it("shows the technical data tab and no description tab", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ProjectTabs
          datiTecnici={[{ label: "Superficie", valore: "600 m²" }]}
          lavorazioni={[]}
          materiali={[]}
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByRole("tab", { name: "Dati tecnici" })).toBeInTheDocument();
    expect(screen.queryByRole("tab", { name: "Descrizione" })).not.toBeInTheDocument();
  });
});
