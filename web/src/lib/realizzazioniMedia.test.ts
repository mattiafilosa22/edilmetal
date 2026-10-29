import { existsSync, readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";

type HistoricProject = { ref: string; media: string[] };

const manifestPath = resolve(process.cwd(), "../cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json");
const mediaRoot = resolve(process.cwd(), "../cms/seed/media/realizzazioni");

/** Gallery riordinate a mano: la foto dell'opera finita non era l'ultimo file. */
const curatedOrder: Record<string, string[]> = {
  "strutture-acciaio-speedy-block": ["01", "03", "02"],
  "pensiline-furia": ["01", "02"],
  "pensiline-italbox-pensilina": ["01", "02"],
  "coperture-tamponamenti-mercantile": ["02", "03", "05", "04", "01"],
  "rivestimenti-facciata-meta": ["04", "02", "01", "05", "03"],
};

const fileNumber = (key: string) => key.slice(key.lastIndexOf("/") + 1);

describe("historic project media", () => {
  it("references existing, uniquely ordered seed assets", () => {
    const projects = JSON.parse(readFileSync(manifestPath, "utf8")) as HistoricProject[];
    const media = projects.flatMap((project) => project.media);
    expect(media).toHaveLength(183);
    expect(new Set(media).size).toBe(183);
    expect(projects.filter((project) => project.media.length > 1)).toHaveLength(51);
    expect(projects.filter((project) => project.media.length === 1)).toHaveLength(31);

    for (const key of media) {
      expect(key.startsWith("real:")).toBe(true);
      expect(existsSync(resolve(mediaRoot, `${key.slice(5)}.jpg`))).toBe(true);
    }

    const incorrectlyOrdered = projects
      .filter((project) => project.media.length > 1)
      .filter((project) => {
        const numbers = project.media.map(fileNumber);
        const expected = curatedOrder[project.ref] ?? [...numbers].sort().reverse();
        return numbers.join() !== expected.join();
      })
      .map((project) => project.ref);
    expect(incorrectlyOrdered).toEqual([]);
    expect(Object.keys(curatedOrder).every((ref) => projects.some((project) => project.ref === ref))).toBe(true);
  });
});
