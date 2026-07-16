import Image from "next/image";
import Link from "next/link";
import { getTranslations } from "next-intl/server";
import type { Locale } from "@/i18n/routing";
import type { SiteSettings } from "@/domain";

type FooterProps = {
  locale: Locale;
  settings: SiteSettings;
};

/**
 * Footer identico su tutte le pagine. Brand, colonne (realizzazioni, azienda,
 * contatti), barra legale. I recapiti provengono da `settings` (WordPress).
 */
export async function Footer({ locale, settings }: FooterProps) {
  const t = await getTranslations("Footer");
  const tNav = await getTranslations("Nav");
  const base = `/${locale}`;
  const phoneHref = `tel:${settings.telefono.replace(/\s/g, "")}`;
  const year = new Date().getFullYear();

  const realizzazioni = [
    "Strutture in acciaio",
    "Pensiline",
    "Scale",
    "Rivestimenti di facciata",
    "Opere speciali",
  ];
  const azienda = [
    { label: t("chiSiamo"), href: `${base}/azienda` },
    { label: tNav("servizi"), href: `${base}/servizi` },
    { label: t("doveSiamo"), href: `${base}/azienda` },
    { label: tNav("contatti"), href: `${base}/contatti` },
  ];

  return (
    <footer className="site-footer">
      <div className="container footer-top">
        <div className="footer-brand">
          <Image
            className="brand__img"
            src="/logo-edilmetal.png"
            alt={settings.nomeAzienda}
            width={165}
            height={42}
          />
          <p>{t("tagline")}</p>
        </div>

        <div>
          <h3>{t("realizzazioni")}</h3>
          <ul>
            {realizzazioni.map((label) => (
              <li key={label}>
                <Link href={`${base}/realizzazioni`}>{label}</Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3>{t("azienda")}</h3>
          <ul>
            {azienda.map((item) => (
              <li key={item.label}>
                <Link href={item.href}>{item.label}</Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3>{t("contatti")}</h3>
          <ul>
            <li>
              <a href={phoneHref}>{settings.telefono}</a>
            </li>
            <li>
              <a href={`mailto:${settings.email}`}>{settings.email}</a>
            </li>
            <li>
              <Link href={`${base}/azienda`}>{settings.indirizzo}</Link>
            </li>
          </ul>
        </div>
      </div>

      <div className="container footer-bottom">
        <span>
          © {year} {settings.ragioneSociale} · P.IVA {settings.partitaIva}
        </span>
        <span>{t("rights")}</span>
      </div>
    </footer>
  );
}
