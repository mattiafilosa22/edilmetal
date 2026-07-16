import type { Metadata } from "next";
import Link from "next/link";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getProgetti } from "@/lib/api";
import {
  anniFrom,
  categorieFrom,
  settoriFrom,
} from "@/lib/mappers/progetto";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { RealizzazioniView } from "@/components/ui/RealizzazioniView";
import { Reveal } from "@/components/ui/Reveal";
import { CtaBand, SectionLabel } from "@/components/ui/blocks";

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
  const tNav = await getTranslations("Nav");

  const categorie = categorieFrom(summaries);
  const settori = settoriFrom(summaries);
  const anni = anniFrom(summaries);

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
            <RealizzazioniView
              summaries={summaries}
              categorie={categorie}
              settori={settori}
              anni={anni}
              locale={locale}
            />
          </div>
        </div>
      </section>

      <section className="section section--alt">
        <div className="container">
          <Reveal>
            <CtaBand
              titolo={t("ctaTitle")}
              testo={t("ctaText")}
              ctaLabel={tNav("ctaPreventivo")}
              ctaHref={`/${locale}/contatti`}
            />
          </Reveal>
        </div>
      </section>
    </>
  );
}
