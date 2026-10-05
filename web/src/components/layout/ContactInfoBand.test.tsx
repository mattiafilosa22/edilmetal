import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { ContactInfoBand } from "./ContactInfoBand";
import messages from "@/i18n/messages/it.json";

const settings = {
  nomeAzienda: "Edilmetal",
  ragioneSociale: "Edilmetal S.r.l.",
  partitaIva: "01234567890",
  indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
  telefono: "0521 615023",
  fax: "0521 615207",
  email: "edilmetal@edilmetal.it",
  coordinate: { lat: 44.8103, lng: 10.1747 },
  orari: [{ giorni: "Lun–Ven", apertura: "08:30–13:00 / 14:30–18:00" }],
  social: {},
};

describe("ContactInfoBand", () => {
  it("shows address, hours and phone", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ContactInfoBand settings={settings} />
      </NextIntlClientProvider>
    );
    expect(screen.getByText(settings.indirizzo)).toBeInTheDocument();
    expect(screen.getByText("Lun–Ven")).toBeInTheDocument();
    expect(screen.getByText("08:30–13:00 / 14:30–18:00")).toBeInTheDocument();
    expect(screen.getByText(/0521 615023/)).toBeInTheDocument();
  });

  it("never shows the fax, even when settings.fax is set", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ContactInfoBand settings={settings} />
      </NextIntlClientProvider>
    );
    expect(screen.queryByText(/0521 615207/)).not.toBeInTheDocument();
    expect(screen.queryByText(/Fax/)).not.toBeInTheDocument();
  });
});
