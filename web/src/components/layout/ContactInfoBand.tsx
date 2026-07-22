import { useTranslations } from "next-intl";
import type { SiteSettings } from "@/domain";

type ContactInfoBandProps = {
  settings: SiteSettings;
};

const mailIcon = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
    <path d="M4 5h16v14H4z" />
    <path d="m4 6 8 7 8-7" />
  </svg>
);
const clockIcon = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
    <circle cx="12" cy="12" r="9" />
    <path d="M12 7v5l3 2" />
  </svg>
);
const phoneIcon = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
    <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" />
  </svg>
);

/**
 * Banda globale "Contatti / Orari ufficio / Telefono" (blocco presente su
 * ogni pagina nel sito storico). Renderizzata nel layout, sopra il footer.
 */
export function ContactInfoBand({ settings }: ContactInfoBandProps) {
  const t = useTranslations("ContactBand");
  const phoneHref = `tel:${settings.telefono.replace(/\s/g, "")}`;

  return (
    <section className="contact-band">
      <div className="container contact-band__grid">
        <div className="contact-band__item">
          <span className="ico">{mailIcon}</span>
          <h3>{t("contattiTitle")}</h3>
          <p>{settings.indirizzo}</p>
          <p>
            <a href={`mailto:${settings.email}`}>{settings.email}</a>
          </p>
        </div>

        <div className="contact-band__item">
          <span className="ico">{clockIcon}</span>
          <h3>{t("orariTitle")}</h3>
          {settings.orari.map((o) => (
            <p key={o.giorni}>
              <span className="contact-band__giorni">{o.giorni}</span>
              <span className="contact-band__apertura">{o.apertura}</span>
            </p>
          ))}
        </div>

        <div className="contact-band__item">
          <span className="ico">{phoneIcon}</span>
          <h3>{t("telefonoTitle")}</h3>
          <p>{t("preventiviText")}</p>
          <p>
            <a href={phoneHref}>{settings.telefono}</a>
            {settings.fax ? (
              <>
                {" · "}
                {t("faxLabel")}: {settings.fax}
              </>
            ) : null}
          </p>
        </div>
      </div>
    </section>
  );
}
