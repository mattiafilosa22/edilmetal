"use client";

import { useSyncExternalStore } from "react";
import { useTranslations } from "next-intl";

type Theme = "light" | "dark";

/** Osserva l'attributo `data-theme` sull'`<html>` (impostato da ThemeScript). */
function subscribe(onChange: () => void): () => void {
  const observer = new MutationObserver(onChange);
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-theme"],
  });
  return () => observer.disconnect();
}

function getSnapshot(): Theme {
  return document.documentElement.getAttribute("data-theme") === "light"
    ? "light"
    : "dark";
}

/** Snapshot lato server: coincide con il default light-first, niente flash. */
function getServerSnapshot(): Theme {
  return "light";
}

/**
 * Toggle tema chiaro/scuro. Lo stato iniziale è già applicato da `ThemeScript`
 * (nessun flash). `useSyncExternalStore` legge il tema dal DOM in modo sicuro
 * per l'idratazione; il MutationObserver aggiorna l'UI al cambio.
 */
export function ThemeToggle() {
  const t = useTranslations("Theme");
  const theme = useSyncExternalStore(subscribe, getSnapshot, getServerSnapshot);

  function toggle() {
    const next: Theme = theme === "dark" ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", next);
    try {
      localStorage.setItem("theme", next);
    } catch {
      /* storage non disponibile: la scelta resta valida per la sessione */
    }
  }

  return (
    <button
      type="button"
      className="theme-toggle"
      onClick={toggle}
      aria-pressed={theme === "dark"}
      title={t("toggle")}
    >
      <svg
        data-icon-moon
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        aria-hidden="true"
      >
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
      </svg>
      <svg
        data-icon-sun
        width="20"
        height="20"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        aria-hidden="true"
      >
        <circle cx="12" cy="12" r="4.5" />
        <path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" />
      </svg>
      <span className="sr-only">
        {theme === "dark" ? t("toLight") : t("toDark")}
      </span>
    </button>
  );
}
