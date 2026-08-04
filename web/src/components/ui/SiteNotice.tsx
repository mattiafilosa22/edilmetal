"use client";

import { useCallback, useEffect, useRef, useState, useSyncExternalStore } from "react";
import { useTranslations } from "next-intl";

/** Chiave di sessione: chiuso l'avviso, non riappare fino alla sessione successiva. */
const STORAGE_KEY = "edilmetal:site-notice-dismissed";

/** La sessione è uno stato esterno a React: si legge, non si duplica. */
const noopSubscribe = () => () => {};

function isDaMostrare(): boolean {
  try {
    return window.sessionStorage.getItem(STORAGE_KEY) !== "1";
  } catch {
    // sessionStorage non disponibile (modalità restrittive): mostra l'avviso.
    return true;
  }
}

/** In SSR/export statico la modale non è mai nel markup. */
const serverFalse = () => false;

const close = (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" aria-hidden="true">
    <path d="M6 6l12 12M18 6L6 18" />
  </svg>
);

/**
 * Avviso modale "sito in aggiornamento", mostrato una sola volta per sessione
 * di navigazione (`sessionStorage`).
 *
 * Usa `<dialog>` nativo: il browser fornisce trappola del focus, chiusura con
 * Esc e inertizzazione del resto della pagina. Il markup viene montato solo
 * dopo l'idratazione, così l'export statico non contiene mai la modale aperta
 * e chi ha JavaScript disattivato non trova un blocco insormontabile.
 */
export function SiteNotice() {
  const t = useTranslations("SiteNotice");
  const [chiuso, setChiuso] = useState(false);
  const dialogRef = useRef<HTMLDialogElement>(null);
  const daMostrare = useSyncExternalStore(noopSubscribe, isDaMostrare, serverFalse);
  const open = daMostrare && !chiuso;

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!open || !dialog || dialog.open) return;
    // `showModal` manca in alcuni ambienti (jsdom): lì basta aprire il dialogo,
    // senza trappola del focus — comportamento accettabile fuori dal browser.
    if (typeof dialog.showModal === "function") dialog.showModal();
    else dialog.setAttribute("open", "");
  }, [open]);

  const dismiss = useCallback(() => {
    try {
      window.sessionStorage.setItem(STORAGE_KEY, "1");
    } catch {
      // Nessuna persistenza possibile: l'avviso ricomparirà, senza rompere nulla.
    }
    dialogRef.current?.close?.();
    setChiuso(true);
  }, []);

  if (!open) return null;

  return (
    <dialog
      ref={dialogRef}
      className="notice"
      aria-labelledby="site-notice-title"
      onCancel={(event) => {
        event.preventDefault();
        dismiss();
      }}
    >
      <div className="notice__grid" aria-hidden="true" />
      <button type="button" className="notice__close" onClick={dismiss} aria-label={t("close")}>
        {close}
      </button>
      <h2 id="site-notice-title" className="notice__title">
        {t("title")}
      </h2>
      <p className="notice__text">{t("text")}</p>
      <button type="button" className="btn btn--deep" onClick={dismiss}>
        {t("cta")}
      </button>
    </dialog>
  );
}
