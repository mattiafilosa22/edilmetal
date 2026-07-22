import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { ProjectCard } from "./ProjectCard";

const baseProgetto = {
  id: "1",
  slug: "bervini",
  luogo: "Noceto (PR)",
  anno: 2015,
  categoria: { slug: "scale" as const, nome: "Scale" },
  inEvidenza: false,
  copertina: { src: "/bervini.jpg", width: 1200, height: 900, alt: "Bervini" },
};

describe("ProjectCard", () => {
  it("does not repeat the client name when titolo and cliente coincide", () => {
    render(
      <ProjectCard
        progetto={{ ...baseProgetto, titolo: "Bervini", cliente: "Bervini" }}
        locale="it"
      />
    );
    expect(screen.getAllByText("Bervini")).toHaveLength(1);
  });

  it("shows both titolo and cliente kicker when they differ", () => {
    render(
      <ProjectCard
        progetto={{ ...baseProgetto, titolo: "Pensilina industriale", cliente: "Parmalat" }}
        locale="it"
      />
    );
    expect(screen.getByText("Pensilina industriale")).toBeInTheDocument();
    expect(screen.getByText("Parmalat")).toBeInTheDocument();
  });
});
