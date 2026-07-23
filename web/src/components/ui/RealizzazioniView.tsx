"use client";

import { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import type { Categoria, CategoriaSlug, ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { ProjectCard } from "./ProjectCard";

type RealizzazioniViewProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  locale: Locale;
  /** Categoria pre-selezionata (es. da un link "vai alla sezione prodotti"). */
  initialCategoria?: CategoriaSlug;
};

const filterIcon = (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
    <path d="M4 6h16M7 12h10M10 18h4" />
  </svg>
);
const closeIcon = (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
    <path d="M18 6 6 18M6 6l12 12" />
  </svg>
);

/**
 * Portfolio realizzazioni filtrabile per sola categoria (fedele all'IA del
 * sito storico: una vista per categoria). Nessun filtro settore/anno, nessun
 * ordinamento, nessuna paginazione — griglia unica con tutti i risultati
 * della categoria scelta (o tutte le realizzazioni se nessuna è selezionata).
 */
export function RealizzazioniView({
  summaries,
  categorie,
  locale,
  initialCategoria,
}: RealizzazioniViewProps) {
  const t = useTranslations("Realizzazioni");
  const [cat, setCat] = useState<CategoriaSlug | null>(initialCategoria ?? null);
  const [drawerOpen, setDrawerOpen] = useState(false);

  useEffect(() => {
    document.body.classList.toggle("filters-open", drawerOpen);
    return () => document.body.classList.remove("filters-open");
  }, [drawerOpen]);

  useEffect(() => {
    if (!drawerOpen) return;
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") setDrawerOpen(false);
    }
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [drawerOpen]);

  const pickCat = (value: CategoriaSlug | null) => setCat(value);

  const filtered = useMemo(() => {
    const out = cat ? summaries.filter((p) => p.categoria.slug === cat) : summaries.slice();
    return out.sort((a, b) => b.anno - a.anno || a.titolo.localeCompare(b.titolo, "it"));
  }, [summaries, cat]);

  const reset = () => setCat(null);

  return (
    <>
      <div className="rz-backdrop" aria-hidden="true" onClick={() => setDrawerOpen(false)} />

      <form className="rz-filters" aria-label={t("filters")} onSubmit={(e) => e.preventDefault()}>
        <div className="rz-filters__head">
          <h2>{t("filters")}</h2>
          <button type="button" className="icon-btn" aria-label={t("closeFilters")} onClick={() => setDrawerOpen(false)}>
            {closeIcon}
          </button>
        </div>

        <div className="rz-fgroup">
          <span className="rz-fgroup__lbl" id="lbl-cat">{t("categoria")}</span>
          <div className="chips" role="group" aria-labelledby="lbl-cat">
            <button type="button" className="chip" aria-pressed={cat === null} onClick={() => pickCat(null)}>{t("tutte")}</button>
            {categorie.map((c) => (
              <button key={c.slug} type="button" className="chip" aria-pressed={cat === c.slug} onClick={() => pickCat(c.slug)}>{c.nome}</button>
            ))}
          </div>
        </div>

        <button type="button" className="btn btn--deep rz-filters__apply" onClick={() => setDrawerOpen(false)}>
          {t("apply")}
        </button>
      </form>

      <div className="rz-toolbar">
        <span className="rz-count" aria-live="polite">
          {t("countFound", { count: filtered.length })}
        </span>
        <button
          type="button"
          className="rz-filters-btn"
          aria-expanded={drawerOpen}
          onClick={() => setDrawerOpen(true)}
        >
          {filterIcon}
          {t("openFilters")}
        </button>
      </div>

      {filtered.length > 0 ? (
        <div className="proj-grid" style={{ marginTop: "var(--sp-6)" }}>
          {filtered.map((p) => (
            <ProjectCard key={p.id} progetto={p} locale={locale} />
          ))}
        </div>
      ) : (
        <div className="rz-empty">
          <h3>{t("emptyTitle")}</h3>
          <p>{t("emptyText")}</p>
          <button type="button" className="btn btn--outline" style={{ marginTop: "var(--sp-4)" }} onClick={reset}>
            {t("resetFilters")}
          </button>
        </div>
      )}
    </>
  );
}
