"use client";

import { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import type { Categoria, CategoriaSlug, ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { ProjectCard } from "./ProjectCard";

type Sort = "recent" | "oldest" | "categoria" | "cliente";

type RealizzazioniViewProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  settori: string[];
  anni: number[];
  locale: Locale;
};

const PAGE_SIZE = 9;

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
 * Portfolio realizzazioni filtrabile lato client sul dataset statico.
 * Chip categoria/settore/anno, ordinamento, drawer off-canvas su mobile,
 * griglia e paginazione. Accessibile: chip come toggle `aria-pressed`,
 * drawer con `aria-expanded`/Esc, contatore live.
 */
export function RealizzazioniView({
  summaries,
  categorie,
  settori,
  anni,
  locale,
}: RealizzazioniViewProps) {
  const t = useTranslations("Realizzazioni");
  const [cat, setCat] = useState<CategoriaSlug | null>(null);
  const [set, setSet] = useState<string | null>(null);
  const [anno, setAnno] = useState<number | null>(null);
  const [sort, setSort] = useState<Sort>("recent");
  const [page, setPage] = useState(1);
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

  // Ogni cambio di filtro/ordinamento riporta a pagina 1 (senza effetti).
  const pickCat = (value: CategoriaSlug | null) => {
    setCat(value);
    setPage(1);
  };
  const pickSet = (value: string | null) => {
    setSet(value);
    setPage(1);
  };
  const pickAnno = (value: number | null) => {
    setAnno(value);
    setPage(1);
  };
  const pickSort = (value: Sort) => {
    setSort(value);
    setPage(1);
  };

  const filtered = useMemo(() => {
    const out = summaries.filter((p) => {
      if (cat && p.categoria.slug !== cat) return false;
      if (set && p.settore !== set) return false;
      if (anno !== null && p.anno !== anno) return false;
      return true;
    });
    out.sort((a, b) => {
      switch (sort) {
        case "oldest":
          return a.anno - b.anno || a.titolo.localeCompare(b.titolo, "it");
        case "categoria":
          return a.categoria.nome.localeCompare(b.categoria.nome, "it");
        case "cliente":
          return a.cliente.localeCompare(b.cliente, "it");
        default:
          return b.anno - a.anno || a.titolo.localeCompare(b.titolo, "it");
      }
    });
    return out;
  }, [summaries, cat, set, anno, sort]);

  const pageCount = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
  const current = Math.min(page, pageCount);
  const visible = filtered.slice((current - 1) * PAGE_SIZE, current * PAGE_SIZE);

  const reset = () => {
    setCat(null);
    setSet(null);
    setAnno(null);
  };

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

        <div className="rz-fgroup">
          <span className="rz-fgroup__lbl" id="lbl-set">{t("settore")}</span>
          <div className="chips" role="group" aria-labelledby="lbl-set">
            <button type="button" className="chip" aria-pressed={set === null} onClick={() => pickSet(null)}>{t("tutti")}</button>
            {settori.map((s) => (
              <button key={s} type="button" className="chip" aria-pressed={set === s} onClick={() => pickSet(s)}>{s}</button>
            ))}
          </div>
        </div>

        <div className="rz-fgroup">
          <span className="rz-fgroup__lbl" id="lbl-anno">{t("anno")}</span>
          <div className="chips" role="group" aria-labelledby="lbl-anno">
            <button type="button" className="chip" aria-pressed={anno === null} onClick={() => pickAnno(null)}>{t("tutti")}</button>
            {anni.map((a) => (
              <button key={a} type="button" className="chip" aria-pressed={anno === a} onClick={() => pickAnno(a)}>{a}</button>
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
        <div className="rz-toolbar__right">
          <button
            type="button"
            className="rz-filters-btn"
            aria-expanded={drawerOpen}
            onClick={() => setDrawerOpen(true)}
          >
            {filterIcon}
            {t("openFilters")}
          </button>
          <div className="rz-sort">
            <label htmlFor="rz-sort-select">{t("sortLabel")}</label>
            <select
              className="select"
              id="rz-sort-select"
              value={sort}
              onChange={(e) => pickSort(e.target.value as Sort)}
            >
              <option value="recent">{t("sortRecent")}</option>
              <option value="oldest">{t("sortOldest")}</option>
              <option value="categoria">{t("sortCategoria")}</option>
              <option value="cliente">{t("sortCliente")}</option>
            </select>
          </div>
        </div>
      </div>

      {visible.length > 0 ? (
        <div className="proj-grid" style={{ marginTop: "var(--sp-6)" }}>
          {visible.map((p) => (
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

      {pageCount > 1 ? (
        <nav className="pager" aria-label="Paginazione">
          <button
            type="button"
            className={current <= 1 ? "is-disabled" : undefined}
            aria-disabled={current <= 1}
            onClick={() => setPage((p) => Math.max(1, p - 1))}
          >
            ←
          </button>
          {Array.from({ length: pageCount }, (_, i) => i + 1).map((n) => (
            <button
              key={n}
              type="button"
              aria-current={n === current ? "page" : undefined}
              onClick={() => setPage(n)}
            >
              {n}
            </button>
          ))}
          <button
            type="button"
            className={current >= pageCount ? "is-disabled" : undefined}
            aria-disabled={current >= pageCount}
            onClick={() => setPage((p) => Math.min(pageCount, p + 1))}
          >
            →
          </button>
        </nav>
      ) : null}
    </>
  );
}
