import type { Metadata } from "next";
import Link from "next/link";
import { Suspense } from "react";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getProgetti } from "@/lib/api";
import { categorieFrom } from "@/lib/mappers/progetto";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { RealizzazioniView } from "@/components/ui/RealizzazioniView";
import { RealizzazioniViewFromQuery } from "@/components/ui/RealizzazioniViewFromQuery";
import { SectionLabel } from "@/components/ui/blocks";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("realizzazioni"),
    siteName: t("siteName"),
    heading: t("realizzazioniHeading"),
    description: t("realizzazioniDescription"),
  });
}

export default async function RealizzazioniPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const summaries = await getProgetti({ locale });
  const t = await getTranslations("Realizzazioni");
  const tSeo = await getTranslations("SEO");

  const categorie = categorieFrom(summaries);

  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: tSeo("realizzazioniHeading"), url: absoluteUrl(localePath(locale, "realizzazioni")) },
  ]);

  return (
    <>
      <JsonLd data={breadcrumbJsonLd} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">{t("current")}</span>
          </nav>
          <h1>{tSeo("realizzazioniHeading")}.</h1>
          <p>{tSeo("realizzazioniDescription")}</p>
        </div>
      </section>

      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("portfolioKick")} />
          <div>
            <Suspense
              fallback={<RealizzazioniView summaries={summaries} categorie={categorie} locale={locale} />}
            >
              <RealizzazioniViewFromQuery
                summaries={summaries}
                categorie={categorie}
                locale={locale}
              />
            </Suspense>
          </div>
        </div>
      </section>
    </>
  );
}
