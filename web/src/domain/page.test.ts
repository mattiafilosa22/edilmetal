import { describe, expect, it } from "vitest";
import { aziendaContentSchema, homeContentSchema, serviziContentSchema } from "./page";

const hero = {
  title: "Strutture in acciaio",
  subtitle: "Progettazione, produzione e montaggio su commessa.",
  ctaPrimary: { label: "Le realizzazioni", href: "/realizzazioni" },
  index: [],
};

describe("homeContentSchema", () => {
  it("accepts hero + up to 2 featured categories", () => {
    const parsed = homeContentSchema.parse({
      hero,
      inEvidenza: [
        {
          categoria: { slug: "strutture-acciaio", nome: "Strutture in acciaio" },
          immagine: { src: "/a.jpg", width: 1200, height: 800, alt: "Struttura in acciaio" },
        },
      ],
    });
    expect(parsed.inEvidenza).toHaveLength(1);
  });

  it("rejects a third featured category", () => {
    const three = Array.from({ length: 3 }, (_, i) => ({
      categoria: { slug: "scale", nome: "Scale" },
      immagine: { src: `/${i}.jpg`, width: 1200, height: 800, alt: "Scala" },
    }));
    expect(() => homeContentSchema.parse({ hero, inEvidenza: three })).toThrow();
  });

  it("no longer accepts the old stats/processo/perche/referenze/cta shape as required", () => {
    const parsed = homeContentSchema.parse({ hero });
    expect(parsed.inEvidenza).toEqual([]);
  });
});

describe("serviziContentSchema", () => {
  it("accepts only { tipologie }", () => {
    const parsed = serviziContentSchema.parse({
      tipologie: [{ slug: "scale", nome: "Scale", dettaglio: "Interne · esterne" }],
    });
    expect(parsed.tipologie).toHaveLength(1);
  });

  it("defaults tipologie to an empty array when absent", () => {
    const parsed = serviziContentSchema.parse({});
    expect(parsed.tipologie).toEqual([]);
  });
});

describe("aziendaContentSchema", () => {
  it("accepts only { storiaTitolo, storia }", () => {
    const parsed = aziendaContentSchema.parse({
      storiaTitolo: "Dal 1997.",
      storia: ["Primo paragrafo.", "Secondo paragrafo."],
    });
    expect(parsed.storia).toHaveLength(2);
  });

  it("requires storiaTitolo (non-empty)", () => {
    expect(() => aziendaContentSchema.parse({ storia: [] })).toThrow();
  });
});
