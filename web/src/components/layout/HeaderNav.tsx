"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { useTranslations } from "next-intl";
import type { Locale } from "@/i18n/routing";
import { ThemeToggle } from "./ThemeToggle";
import { LocaleSwitcher } from "./LocaleSwitcher";

type NavItem = { key: string; segment: string; label: string };

type HeaderNavProps = {
  locale: Locale;
  /** Recapiti mostrati nel piede dell'overlay (dai settings WP). */
  phone: string;
  email: string;
  indirizzo: string;
};

const menuIcon = (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden="true">
    <path d="M3 6h18M3 12h18M3 18h18" />
  </svg>
);

const closeIcon = (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
    <path d="M18 6 6 18M6 6l12 12" />
  </svg>
);

/**
 * Navigazione dell'header: nav in linea (desktop), cluster strumenti (tema,
 * lingua) e overlay menù full-screen (≤960px o hamburger).
 * `usePathname` determina la voce attiva (`aria-current`). L'overlay usa la
 * classe `menu-open` sul body (coerente con il CSS del design system).
 */
export function HeaderNav({ locale, phone, email, indirizzo }: HeaderNavProps) {
  const t = useTranslations("Nav");
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  const base = `/${locale}`;
  const items: NavItem[] = [
    { key: "home", segment: "", label: t("home") },
    { key: "realizzazioni", segment: "realizzazioni", label: t("realizzazioni") },
    { key: "servizi", segment: "servizi", label: t("servizi") },
    { key: "azienda", segment: "azienda", label: t("azienda") },
    { key: "contatti", segment: "contatti", label: t("contatti") },
  ];

  const parts = pathname.split("/").filter(Boolean);
  const current = parts[1] ?? "";

  const close = useCallback(() => setOpen(false), []);

  useEffect(() => {
    document.body.classList.toggle("menu-open", open);
    return () => document.body.classList.remove("menu-open");
  }, [open]);

  useEffect(() => {
    if (!open) return;
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") setOpen(false);
    }
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [open]);

  const hrefFor = (segment: string) => (segment ? `${base}/${segment}` : base);

  return (
    <>
      <nav className="hdr-nav" aria-label={t("ariaLabel")}>
        {items.map((item) => (
          <Link
            key={item.key}
            href={hrefFor(item.segment)}
            aria-current={item.segment === current ? "page" : undefined}
          >
            {item.label}
          </Link>
        ))}
      </nav>

      <div className="hdr-tools">
        <LocaleSwitcher />
        <ThemeToggle />
        <button
          type="button"
          className="menu-btn"
          aria-label={t("openMenu")}
          aria-expanded={open}
          onClick={() => setOpen(true)}
        >
          {menuIcon}
          {t("menu")}
        </button>
      </div>

      {open &&
        createPortal(
          <div className="overlay-nav" aria-label={t("menu")}>
            <div className="overlay-nav__top">
              <span className="mono" style={{ color: "rgba(255,255,255,.7)" }}>
                {t("menuTitle")}
              </span>
              <button
                type="button"
                className="icon-btn"
                aria-label={t("closeMenu")}
                onClick={close}
                style={{ color: "#fff", borderColor: "rgba(255,255,255,.3)", background: "transparent" }}
              >
                {closeIcon}
              </button>
            </div>
            <nav className="overlay-nav__list" aria-label={t("pagesAriaLabel")}>
              {items.map((item, index) => (
                <Link key={item.key} href={hrefFor(item.segment)} onClick={close}>
                  <span className="idx">{String(index).padStart(2, "0")}</span> {item.label}
                </Link>
              ))}
            </nav>
            <div className="overlay-nav__foot">
              <span>{phone}</span>
              <span>{email}</span>
              <span>{indirizzo}</span>
            </div>
          </div>,
          document.body
        )}
    </>
  );
}
