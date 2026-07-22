import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage, getProgetti, getSettings } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildMetadata,
  buildOrganizationJsonLd,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { Hero } from "@/components/ui/Hero";
import { FeaturedCategories } from "@/components/ui/FeaturedCategories";
import { RealizzazioniBand } from "@/components/ui/RealizzazioniBand";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor(),
    siteName: t("siteName"),
    heading: t("homeHeading"),
    description: t("homeDescription"),
  });
}

export default async function HomePage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const [page, evidenza, settings] = await Promise.all([
    getPage({ locale, key: "home" }),
    getProgetti({ locale, filters: { inEvidenza: true } }),
    getSettings({ locale }),
  ]);

  const t = await getTranslations("Home");
  const home = page?.home;

  const organizationJsonLd = buildOrganizationJsonLd({
    settings,
    url: absoluteUrl(localePath(locale)),
    logoUrl: absoluteUrl("/logo-edilmetal.png"),
  });

  if (!home) {
    return <JsonLd data={organizationJsonLd} />;
  }

  return (
    <>
      <JsonLd data={organizationJsonLd} />

      <Hero
        hero={home.hero}
        locale={locale}
        heroImage={settings.heroImage}
        fotoCredit={settings.fotoCredit}
      />

      {/* In evidenza — 2 categorie con foto reale (blocco storico) */}
      <section className="section">
        <div className="container">
          <span className="sec-label">
            <span className="num">01</span>
            <span className="kick">{t("categorieKick")}</span>
            <span className="bar" />
          </span>
          <div className="mt-6">
            <FeaturedCategories items={home.inEvidenza} locale={locale} />
          </div>
        </div>
      </section>

      {/* Realizzazioni — banda scura con foto reali (blocco storico) */}
      <RealizzazioniBand
        progetti={evidenza}
        locale={locale}
        num="02"
        kick={t("evidenzaKick")}
        title={t("evidenzaTitle")}
        subtitle={t("evidenzaSub")}
        ctaLabel={t("tutteLeRealizzazioni")}
        ctaHref={`/${locale}/realizzazioni`}
      />
    </>
  );
}
