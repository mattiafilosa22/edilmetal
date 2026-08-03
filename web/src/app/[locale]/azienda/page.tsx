import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage, getSettings } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  buildOrganizationJsonLd,
  localePath,
  localePathsFor,
} from "@/lib/seo";
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
    paths: localePathsFor("azienda"),
    siteName: t("siteName"),
    heading: t("aziendaHeading"),
    description: t("aziendaDescription"),
  });
}

export default async function AziendaPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const [page, settings] = await Promise.all([
    getPage({ locale, key: "azienda" }),
    getSettings({ locale }),
  ]);
  const azienda = page?.azienda;
  if (!page || !azienda) notFound();

  const t = await getTranslations("Azienda");

  const orgJsonLd = buildOrganizationJsonLd({
    settings,
    url: absoluteUrl(localePath(locale, "azienda")),
    logoUrl: absoluteUrl("/logo-edilmetal.png"),
    type: "LocalBusiness",
    withPlaceData: true,
  });
  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: t("current"), url: absoluteUrl(localePath(locale, "azienda")) },
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
          <h1>{page.title}.</h1>
          {page.subtitle ? <p>{page.subtitle}</p> : null}
        </div>
      </section>

      {/* Storia (come il vecchio "LA SOCIETA'") */}
      <section className="section">
        <div className="container">
          <Reveal className="sec-head">
            <h2>{azienda.storiaTitolo}</h2>
          </Reveal>
          {azienda.storia.map((par, i) => (
            <Reveal key={i} className="mt-4">
              <p style={{ color: "var(--ink-2)" }}>{par}</p>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
