import { describe, expect, it } from "vitest";
import type { Progetto } from "@/domain";
import { anonimizzaProgetto, anonimizzaTesto } from "./anonimizza";

const dati = { cliente: "Bervini", titolo: "Bervini", anno: 2018 };

describe("anonimizzaTesto", () => {
  it("removes client name and year from the seeded description template", () => {
    const testo =
      "<p>Realizzazione Edilmetal per Bervini a Provincia di Parma (2018): realizzazione in carpenteria metallica per Bervini. L’intervento è stato gestito come unico interlocutore.</p>";
    expect(anonimizzaTesto(testo, dati)).toBe(
      "<p>Realizzazione Edilmetal a Provincia di Parma: realizzazione in carpenteria metallica. L’intervento è stato gestito come unico interlocutore.</p>"
    );
  });

  it("removes the location together with its preposition", () => {
    const testo =
      "<p>Realizzazione Edilmetal per Bervini a Provincia di Parma (2018): realizzazione in carpenteria metallica.</p>";
    expect(anonimizzaTesto(testo, { ...dati, luogo: "Provincia di Parma" })).toBe(
      "<p>Realizzazione Edilmetal: realizzazione in carpenteria metallica.</p>"
    );
  });

  it("removes a location ending with a parenthesis", () => {
    expect(
      anonimizzaTesto("Scala a Noceto (PR), su misura.", {
        cliente: "X",
        titolo: "X",
        anno: 2018,
        luogo: "Noceto (PR)",
      })
    ).toBe("Scala, su misura.");
  });

  it("does not cut a location out of a longer word", () => {
    expect(
      anonimizzaTesto("Capannone Parmalat a Parma.", {
        cliente: "X",
        titolo: "X",
        anno: 2018,
        luogo: "Parma",
      })
    ).toBe("Capannone Parmalat.");
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

const progetto: Progetto = {
  id: "1",
  slug: "scale-plan",
  titolo: "Plan",
  cliente: "Plan",
  luogo: "Provincia di Parma",
  anno: 2018,
  categoria: { slug: "scale", nome: "Scale" },
  inEvidenza: false,
  descrizione: "<p>Realizzazione Edilmetal per Plan (2018).</p>",
  galleria: [{ src: "/1.jpg", width: 100, height: 80, alt: "Edilmetal — Scale Plan (foto)" }],
  datiTecnici: [{ label: "Tipologia", valore: "Realizzazione in carpenteria metallica per Plan" }],
  lavorazioni: ["Montaggio per Plan"],
  materiali: ["Acciaio S275"],
  seo: {
    title: "Plan — Edilmetal",
    description: "Case study Edilmetal: realizzazione in carpenteria metallica per Plan a Provincia di Parma (2018).",
    ogImage: "/og.jpg",
  },
};

describe("anonimizzaProgetto", () => {
  it("cleans every free text surface of the project sheet", () => {
    const pulito = anonimizzaProgetto(progetto);
    expect(pulito.descrizione).toBe("<p>Realizzazione Edilmetal.</p>");
    expect(pulito.galleria[0].alt).toBe("Edilmetal — Scale (foto)");
    expect(pulito.datiTecnici[0].valore).toBe("Realizzazione in carpenteria metallica");
    expect(pulito.lavorazioni).toEqual(["Montaggio"]);
    expect(pulito.materiali).toEqual(["Acciaio S275"]);
    expect(pulito.seo?.description).toBe(
      "Case study Edilmetal: realizzazione in carpenteria metallica."
    );
  });

  it("drops a SEO title that no longer says anything, so the page falls back", () => {
    expect(anonimizzaProgetto(progetto).seo?.title).toBeUndefined();
  });

  it("keeps client, title and year on the entity: they only get hidden in the UI", () => {
    const pulito = anonimizzaProgetto(progetto);
    expect(pulito.cliente).toBe("Plan");
    expect(pulito.anno).toBe(2018);
  });
});
