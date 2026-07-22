import { describe, expect, it } from "vitest";
import { progettoDisplayName } from "./progetto";

describe("progettoDisplayName", () => {
  it("returns only the titolo when titolo and cliente coincide (progetti storici)", () => {
    expect(progettoDisplayName({ titolo: "Bervini", cliente: "Bervini" })).toBe("Bervini");
  });

  it("returns 'titolo — cliente' when they differ", () => {
    expect(progettoDisplayName({ titolo: "Pensilina industriale", cliente: "Parmalat" })).toBe(
      "Pensilina industriale — Parmalat"
    );
  });
});
