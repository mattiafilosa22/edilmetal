import "@testing-library/jest-dom/vitest";
import { render, screen, within } from "@testing-library/react";
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
  it("shows the family of works as the visible title", () => {
    render(
      <ProjectCard
        progetto={{ ...baseProgetto, titolo: "Bervini", cliente: "Bervini" }}
        locale="it"
      />
    );
    expect(screen.getByRole("heading", { name: "Scale" })).toBeInTheDocument();
    expect(screen.getByText("Noceto (PR)")).toBeInTheDocument();
  });

  it("keeps client name and year in the markup, but visually hidden", () => {
    render(
      <ProjectCard
        progetto={{ ...baseProgetto, titolo: "Pensilina industriale", cliente: "Parmalat" }}
        locale="it"
      />
    );
    expect(screen.getByText("Pensilina industriale — Parmalat")).toHaveClass("is-hidden-data");
    expect(screen.getByText("2015")).toHaveClass("is-hidden-data");
  });

  it("uses one link for the image, heading and location", () => {
    render(
      <ProjectCard
        progetto={{ ...baseProgetto, titolo: "Bervini", cliente: "Bervini" }}
        locale="it"
      />
    );
    const links = screen.getAllByRole("link");

    expect(links).toHaveLength(1);
    expect(links[0]).toHaveAttribute("href", "/it/realizzazioni/bervini");
    expect(within(links[0]).getByRole("img", { name: "Bervini" })).toBeInTheDocument();
    expect(within(links[0]).getByRole("heading", { name: "Scale" })).toBeInTheDocument();
    expect(within(links[0]).getByText("Noceto (PR)")).toBeInTheDocument();
  });
});
