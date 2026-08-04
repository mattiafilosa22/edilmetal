import { describe, expect, it } from "vitest";
import { isProgettoNascosto } from "./visibilita";

describe("isProgettoNascosto", () => {
  it("hides the projects without a release from the client", () => {
    expect(isProgettoNascosto("strutture-miste-aiassa")).toBe(true);
    expect(isProgettoNascosto("scale-pinko")).toBe(true);
    expect(isProgettoNascosto("pensiline-pinko")).toBe(true);
  });

  it("hides the Polylang translations of the same projects", () => {
    expect(isProgettoNascosto("scale-pinko-2")).toBe(true);
  });

  it("leaves every other project visible", () => {
    expect(isProgettoNascosto("strutture-acciaio-bervini")).toBe(false);
    expect(isProgettoNascosto("pensiline-immergas-mag07")).toBe(false);
    expect(isProgettoNascosto("scale-plan")).toBe(false);
  });
});
