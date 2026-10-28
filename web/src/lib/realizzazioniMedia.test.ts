import { existsSync, readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";

type HistoricProject = { ref: string; media: string[] };

const manifestPath = resolve(process.cwd(), "../cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json");
const mediaRoot = resolve(process.cwd(), "../cms/seed/media/realizzazioni");

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
        const positions = project.media.map((key) => Number(key.slice(key.lastIndexOf("/") + 1)));
        return positions.some((position, index) => index > 0 && positions[index - 1] < position);
      })
      .map((project) => project.ref);
    expect(incorrectlyOrdered).toEqual([]);
  });
});
