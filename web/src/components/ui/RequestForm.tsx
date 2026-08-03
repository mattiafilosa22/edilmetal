"use client";

import { useId, useState, type FormEvent } from "react";
import Link from "next/link";
import { useTranslations } from "next-intl";
import type { CategoriaSlug } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { submitLead, type LeadRequest } from "@/lib/forms";

export type CategoriaOption = { slug: CategoriaSlug; nome: string };

type RequestFormProps = {
  categorie: CategoriaOption[];
  locale: Locale;
  /** Variante compatta ("progetto simile" nella scheda): meno campi. */
  compact?: boolean;
  /** Slug del progetto sorgente (scheda), inviato come metadato. */
  progettoSlug?: string;
  /** Tipo di opera preselezionato (variante compatta). */
  defaultTipoOpera?: CategoriaSlug;
};

type Status = "idle" | "sending" | "success" | "error";
type Errors = Partial<Record<"nome" | "email" | "tipoOpera" | "messaggio" | "gdpr", string>>;

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Form richiesta preventivo, accessibile e riusabile (contatti + scheda).
 * Label associate, `aria-invalid`/`aria-describedby`, errori con `role="alert"`,
 * consenso GDPR obbligatorio e honeypot anti-bot. Invio disaccoppiato via
 * `submitLead` (POST /lead). Nessun testo editoriale qui: solo label UI (i18n).
 */
export function RequestForm({
  categorie,
  locale,
  compact = false,
  progettoSlug,
  defaultTipoOpera,
}: RequestFormProps) {
  const t = useTranslations("Form");
  const uid = useId();
  const [status, setStatus] = useState<Status>("idle");
  const [errors, setErrors] = useState<Errors>({});

  const id = (name: string) => `${uid}-${name}`;
  const fallbackTipo = defaultTipoOpera ?? categorie[0]?.slug;

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    // Honeypot: se compilato è un bot → simuliamo il successo senza inviare.
    if (String(data.get("website") ?? "").length > 0) {
      setStatus("success");
      form.reset();
      return;
    }

    const nome = String(data.get("nome") ?? "").trim();
    const email = String(data.get("email") ?? "").trim();
    const messaggio = String(data.get("messaggio") ?? "").trim();
    const tipoOpera = compact
      ? fallbackTipo
      : (String(data.get("tipoOpera") ?? "") as CategoriaSlug | "");
    const consent = data.get("gdpr") === "on";

    const next: Errors = {};
    if (nome.length < 2) next.nome = t("required");
    if (!email) next.email = t("required");
    else if (!EMAIL_RE.test(email)) next.email = t("invalidEmail");
    if (!compact && !tipoOpera) next.tipoOpera = t("required");
    if (messaggio.length < 10) next.messaggio = t("required");
    if (!consent) next.gdpr = t("consentRequired");

    setErrors(next);
    if (Object.keys(next).length > 0) {
      document.getElementById(id(Object.keys(next)[0]))?.focus();
      return;
    }

    const payload: LeadRequest = {
      nome,
      azienda: String(data.get("azienda") ?? "").trim() || undefined,
      email,
      telefono: String(data.get("telefono") ?? "").trim() || undefined,
      tipoOpera: tipoOpera as CategoriaSlug,
      messaggio,
      progettoSlug,
      gdpr: true,
    };

    setStatus("sending");
    try {
      await submitLead(payload);
      setStatus("success");
      form.reset();
    } catch {
      setStatus("error");
    }
  }

  const req = <span className="req" aria-hidden="true">*</span>;

  return (
    <form
      className={compact ? "sp-cta" : "ct-form"}
      onSubmit={onSubmit}
      noValidate
      aria-label={t("submit")}
    >
      <div className={compact ? "sp-cta__grid" : "ct-form__grid"}>
        <div className="field">
          <label htmlFor={id("nome")}>{t("nome")} {req}</label>
          <input
            className="input"
            id={id("nome")}
            name="nome"
            type="text"
            autoComplete="name"
            aria-required
            aria-invalid={errors.nome ? true : undefined}
            aria-describedby={errors.nome ? `${id("nome")}-err` : undefined}
          />
          {errors.nome ? <span className="field-error" id={`${id("nome")}-err`} role="alert">{errors.nome}</span> : null}
        </div>

        {!compact ? (
          <div className="field">
            <label htmlFor={id("azienda")}>{t("azienda")}</label>
            <input className="input" id={id("azienda")} name="azienda" type="text" autoComplete="organization" />
          </div>
        ) : null}

        <div className="field">
          <label htmlFor={id("email")}>{t("email")} {req}</label>
          <input
            className="input"
            id={id("email")}
            name="email"
            type="email"
            autoComplete="email"
            aria-required
            aria-invalid={errors.email ? true : undefined}
            aria-describedby={errors.email ? `${id("email")}-err` : undefined}
          />
          {errors.email ? <span className="field-error" id={`${id("email")}-err`} role="alert">{errors.email}</span> : null}
        </div>

        {!compact ? (
          <div className="field">
            <label htmlFor={id("telefono")}>{t("telefono")}</label>
            <input className="input" id={id("telefono")} name="telefono" type="tel" autoComplete="tel" />
          </div>
        ) : null}

        {!compact ? (
          <div className="field field--full">
            <label htmlFor={id("tipoOpera")}>{t("tipoOpera")} {req}</label>
            <select
              className="select"
              id={id("tipoOpera")}
              name="tipoOpera"
              defaultValue=""
              aria-required
              aria-invalid={errors.tipoOpera ? true : undefined}
              aria-describedby={errors.tipoOpera ? `${id("tipoOpera")}-err` : undefined}
            >
              <option value="">{t("selectTipo")}</option>
              {categorie.map((c) => (
                <option key={c.slug} value={c.slug}>{c.nome}</option>
              ))}
            </select>
            {errors.tipoOpera ? <span className="field-error" id={`${id("tipoOpera")}-err`} role="alert">{errors.tipoOpera}</span> : null}
          </div>
        ) : null}

        <div className="field field--full">
          <label htmlFor={id("messaggio")}>{compact ? t("esigenza") : t("messaggio")} {req}</label>
          <textarea
            className="textarea"
            id={id("messaggio")}
            name="messaggio"
            placeholder={compact ? t("esigenzaPlaceholder") : t("messaggioPlaceholder")}
            aria-required
            aria-invalid={errors.messaggio ? true : undefined}
            aria-describedby={errors.messaggio ? `${id("messaggio")}-err` : undefined}
          />
          {errors.messaggio ? <span className="field-error" id={`${id("messaggio")}-err`} role="alert">{errors.messaggio}</span> : null}
        </div>
      </div>

      {/* Honeypot: fuori schermo e fuori dal tab order. */}
      <div className="hp" aria-hidden="true">
        <label htmlFor={id("website")}>Website</label>
        <input id={id("website")} name="website" type="text" tabIndex={-1} autoComplete="off" />
      </div>

      <div className="consent">
        <input
          id={id("gdpr")}
          name="gdpr"
          type="checkbox"
          aria-required
          aria-invalid={errors.gdpr ? true : undefined}
          aria-describedby={errors.gdpr ? `${id("gdpr")}-err` : undefined}
        />
        <label htmlFor={id("gdpr")}>
          {t("gdpr")}<Link href={`/${locale}/privacy-policy`}>{t("gdprLink")}</Link> {req}
          {errors.gdpr ? <span className="field-error" id={`${id("gdpr")}-err`} role="alert"> {errors.gdpr}</span> : null}
        </label>
      </div>

      <button type="submit" className="btn btn--accent btn--lg" disabled={status === "sending"}>
        {status === "sending" ? t("sending") : t("submit")}
      </button>
      <p className="mono" style={{ marginTop: "var(--sp-4)" }}>{t("requiredNote")}</p>

      <p className={`form-status form-status--${status}`} role="status" aria-live="polite">
        {status === "success" ? t("success") : null}
        {status === "error" ? t("failure") : null}
      </p>
    </form>
  );
}
