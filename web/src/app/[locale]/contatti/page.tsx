import type { Metadata } from "next";
import Link from "next/link";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage, getProgetti, getSettings } from "@/lib/api";
import { categorieFrom } from "@/lib/mappers/progetto";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  buildOrganizationJsonLd,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { RequestForm } from "@/components/ui/RequestForm";
import { Reveal } from "@/components/ui/Reveal";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("contatti"),
    siteName: t("siteName"),
    heading: t("contattiHeading"),
    description: t("contattiDescription"),
  });
}

const phoneIcon = (
  <svg className="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
    <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.4-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z" />
  </svg>
);
const mailIcon = (
  <svg className="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
    <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
    <path d="m22 6-10 7L2 6" />
  </svg>
);
const pinIcon = (
  <svg className="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
    <path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0 1 18 0z" />
    <circle cx="12" cy="10" r="3" />
  </svg>
);
const clockIcon = (
  <svg className="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
    <circle cx="12" cy="12" r="9" />
    <path d="M12 7v5l3 2" />
  </svg>
);

export default async function ContattiPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const [page, summaries, settings] = await Promise.all([
    getPage({ locale, key: "contatti" }),
    getProgetti({ locale }),
    getSettings({ locale }),
  ]);

  const t = await getTranslations("Contatti");
  const categorie = categorieFrom(summaries);
  const orari = settings.orari.map((o) => `${o.giorni} ${o.apertura}`).join(" · ");

  const orgJsonLd = buildOrganizationJsonLd({
    settings,
    url: absoluteUrl(localePath(locale, "contatti")),
    logoUrl: absoluteUrl("/logo-edilmetal.png"),
    type: "LocalBusiness",
    withPlaceData: true,
  });
  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: t("current"), url: absoluteUrl(localePath(locale, "contatti")) },
  ]);

  return (
    <>
      <JsonLd data={[orgJsonLd, breadcrumbJsonLd]} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">{t("current")}</span>
          </nav>
          <h1>{page?.title ?? t("current")}.</h1>
          {page?.subtitle ? <p>{page.subtitle}</p> : null}
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="ct-layout">
            <Reveal>
              <RequestForm categorie={categorie} locale={locale} />
            </Reveal>

            <aside className="ct-info">
              <Reveal className="ct-card">
                <h2>{t("contattiDiretti")}</h2>
                <div className="ct-row">
                  {phoneIcon}
                  <span>
                    <a href={`tel:${settings.telefono.replace(/\s/g, "")}`}>{settings.telefono}</a>
                    <small>{t("telefono")}</small>
                  </span>
                </div>
                <div className="ct-row">
                  {mailIcon}
                  <span>
                    <a href={`mailto:${settings.email}`}>{settings.email}</a>
                    <small>{t("email")}</small>
                  </span>
                </div>
                <div className="ct-row">
                  {pinIcon}
                  <span>
                    {settings.indirizzo}
                    <small>{t("sede")}</small>
                  </span>
                </div>
                {orari ? (
                  <div className="ct-row">
                    {clockIcon}
                    <span>
                      {orari}
                      <small>{t("orari")}</small>
                    </span>
                  </div>
                ) : null}
              </Reveal>
            </aside>
          </div>
        </div>
      </section>
    </>
  );
}
