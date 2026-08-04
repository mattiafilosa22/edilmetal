import { describe, expect, it } from "vitest";
import { anonimizzaTesto } from "./anonimizza";

const dati = { cliente: "Bervini", titolo: "Bervini", anno: 2018 };

describe("anonimizzaTesto", () => {
  it("removes client name and year from the seeded description template", () => {
    const testo =
      "<p>Realizzazione Edilmetal per Bervini a Provincia di Parma (2018): realizzazione in carpenteria metallica per Bervini. L’intervento è stato gestito come unico interlocutore.</p>";
    expect(anonimizzaTesto(testo, dati)).toBe(
      "<p>Realizzazione Edilmetal a Provincia di Parma: realizzazione in carpenteria metallica. L’intervento è stato gestito come unico interlocutore.</p>"
    );
  });

  it("removes the name when it appears without a preposition", () => {
    expect(anonimizzaTesto("Commessa Bervini, Noceto.", dati)).toBe("Commessa, Noceto.");
  });

  it("removes both titolo and cliente when they differ", () => {
    const testo = "Pensilina industriale per Parmalat, anno 2015.";
    expect(
      anonimizzaTesto(testo, { titolo: "Pensilina industriale", cliente: "Parmalat", anno: 2015 })
    ).toBe(", anno.");
  });

  it("leaves common lowercase words alone (case-sensitive match)", () => {
    const testo = "Struttura con meta di progetto e piano di montaggio.";
    expect(anonimizzaTesto(testo, { cliente: "Meta", titolo: "Meta", anno: 2018 })).toBe(testo);
  });

  it("leaves a text without references untouched", () => {
    const testo = "Carpenteria metallica su commessa, dal sopralluogo al montaggio.";
    expect(anonimizzaTesto(testo, dati)).toBe(testo);
  });
});
