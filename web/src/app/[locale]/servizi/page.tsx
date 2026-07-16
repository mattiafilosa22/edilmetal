import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { Reveal } from "@/components/ui/Reveal";
import {
  Callout,
  CategoryIndex,
  CtaBand,
  Feats,
  Flow,
  SectionLabel,
} from "@/components/ui/blocks";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("servizi"),
    siteName: t("siteName"),
    heading: t("serviziHeading"),
    description: t("serviziDescription"),
  });
}

export default async function ServiziPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const page = await getPage({ locale, key: "servizi" });
  const servizi = page?.servizi;
  if (!page || !servizi) notFound();

  const t = await getTranslations("Servizi");
  const tNav = await getTranslations("Nav");

  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: t("current"), url: absoluteUrl(localePath(locale, "servizi")) },
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
          <h1>{page.title}.</h1>
          {page.subtitle ? <p>{page.subtitle}</p> : null}
        </div>
      </section>

      {/* 01 · Processo */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("processoKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("processoTitle")}</h2>
              <p>{t("processoSub")}</p>
            </Reveal>
            <Reveal className="mt-8">
              <Flow steps={servizi.processo} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 02 · Tipologie di opere */}
      <section className="section section--alt">
        <div className="container sec-grid">
          <SectionLabel num="02" kick={t("tipologieKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("tipologieTitle")}</h2>
              <p>{t("tipologieSub")}</p>
            </Reveal>
            <Reveal className="mt-6">
              <CategoryIndex categorie={servizi.tipologie} locale={locale} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 03 · Vantaggi */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="03" kick={t("vantaggiKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("vantaggiTitle")}</h2>
            </Reveal>
            <Reveal className="mt-6">
              <Feats items={servizi.vantaggi} />
            </Reveal>
            {servizi.callout ? (
              <Reveal className="mt-6">
                <Callout titolo={servizi.callout.titolo} testo={servizi.callout.testo} />
              </Reveal>
            ) : null}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section section--alt">
        <div className="container">
          <Reveal>
            <CtaBand
              titolo={servizi.cta.titolo}
              testo={servizi.cta.testo}
              ctaLabel={tNav("ctaPreventivo")}
              ctaHref={`/${locale}/contatti`}
            />
          </Reveal>
        </div>
      </section>
    </>
  );
}
