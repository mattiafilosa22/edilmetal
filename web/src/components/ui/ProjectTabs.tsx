"use client";

import { useRef, useState, type ReactNode } from "react";
import { useTranslations } from "next-intl";
import type { DatoTecnico } from "@/domain";

type ProjectTabsProps = {
  descrizione: string;
  datiTecnici: DatoTecnico[];
  lavorazioni: string[];
  materiali: string[];
};

type Tab = { id: string; label: string; panel: ReactNode };

/**
 * Tab tecniche ARIA della scheda progetto (Descrizione / Dati tecnici /
 * Lavorazioni / Materiali). Navigazione da tastiera (frecce, Home, End) con
 * roving tabindex, come da pattern WAI-ARIA. I tab vuoti sono omessi.
 */
export function ProjectTabs({ descrizione, datiTecnici, lavorazioni, materiali }: ProjectTabsProps) {
  const t = useTranslations("Scheda");
  const tabRefs = useRef<Array<HTMLButtonElement | null>>([]);

  const tabs: Tab[] = [];
  tabs.push({
    id: "desc",
    label: t("tabDescrizione"),
    panel: descrizione.split("\n\n").map((p, i) => <p key={i}>{p}</p>),
  });
  if (datiTecnici.length > 0) {
    tabs.push({
      id: "dati",
      label: t("tabDati"),
      panel: (
        <dl className="datalist spec">
          {datiTecnici.map((d) => (
            <div key={d.label}>
              <dt>{d.label}</dt>
              <dd>{d.valore}</dd>
            </div>
          ))}
        </dl>
      ),
    });
  }
  if (lavorazioni.length > 0) {
    tabs.push({
      id: "lav",
      label: t("tabLavorazioni"),
      panel: (
        <ul className="spec">
          {lavorazioni.map((l) => (
            <li key={l}>{l}</li>
          ))}
        </ul>
      ),
    });
  }
  if (materiali.length > 0) {
    tabs.push({
      id: "mat",
      label: t("tabMateriali"),
      panel: (
        <ul className="spec">
          {materiali.map((m) => (
            <li key={m}>{m}</li>
          ))}
        </ul>
      ),
    });
  }

  const [active, setActive] = useState(0);

  function onKeyDown(e: React.KeyboardEvent, i: number) {
    let next = i;
    if (e.key === "ArrowRight") next = (i + 1) % tabs.length;
    else if (e.key === "ArrowLeft") next = (i - 1 + tabs.length) % tabs.length;
    else if (e.key === "Home") next = 0;
    else if (e.key === "End") next = tabs.length - 1;
    else return;
    e.preventDefault();
    setActive(next);
    tabRefs.current[next]?.focus();
  }

  return (
    <div className="section" style={{ paddingBlock: "var(--sp-7) 0" }}>
      <div className="tabs__list" role="tablist" aria-label={t("tablistLabel")}>
        {tabs.map((tab, i) => (
          <button
            key={tab.id}
            ref={(el) => {
              tabRefs.current[i] = el;
            }}
            className="tabs__tab"
            role="tab"
            id={`tab-${tab.id}`}
            aria-controls={`panel-${tab.id}`}
            aria-selected={i === active}
            tabIndex={i === active ? 0 : -1}
            onClick={() => setActive(i)}
            onKeyDown={(e) => onKeyDown(e, i)}
          >
            {tab.label}
          </button>
        ))}
      </div>
      {tabs.map((tab, i) => (
        <div
          key={tab.id}
          className="tabs__panel"
          role="tabpanel"
          id={`panel-${tab.id}`}
          aria-labelledby={`tab-${tab.id}`}
          hidden={i !== active}
        >
          {tab.panel}
        </div>
      ))}
    </div>
  );
}
