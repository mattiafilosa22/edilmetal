import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import type { Image as ImageDto, ProgettoSummary } from "@/domain";
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

/**
 * Realizzazioni che alimentano lo slider dell'hero, dopo la foto di copertina
 * di `settings.heroImage`: si usa la prima foto della loro galleria. Gli slug
 * sono radici — Polylang aggiunge il suffisso della traduzione (`-2`).
 */
const HERO_SLIDER_PROGETTI = ["strutture-acciaio-bervini", "strutture-acciaio-acetum"];

/** Copertina + prime foto delle realizzazioni scelte, senza duplicati né buchi. */
function buildHeroImages(
  copertina: ImageDto | undefined,
  progetti: ProgettoSummary[]
): ImageDto[] {
  const dalleRealizzazioni = HERO_SLIDER_PROGETTI.map(
    (slug) => progetti.find((p) => p.slug.startsWith(slug))?.copertina
  );
  const immagini = [copertina, ...dalleRealizzazioni].filter(
    (img): img is ImageDto => img !== undefined
  );
  return immagini.filter(
    (img, i) => immagini.findIndex((other) => other.src === img.src) === i
  );
}

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

  const [page, evidenza, settings, tutti] = await Promise.all([
    getPage({ locale, key: "home" }),
    getProgetti({ locale, filters: { inEvidenza: true } }),
    getSettings({ locale }),
    getProgetti({ locale }),
  ]);

  const t = await getTranslations("Home");
  const tSeo = await getTranslations("SEO");
  const home = page?.home;

  const heroImages = buildHeroImages(settings.heroImage, tutti);

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

      {/* Heading di pagina, visivamente nascosto: l'Hero in modalità `photoOnly`
          (fedele al sito storico) non renderizza alcun testo, ma la pagina deve
          comunque esporre esattamente un `h1` per la struttura semantica/screen reader. */}
      <h1 className="sr-only">{tSeo("homeHeading")}</h1>

      <Hero
        hero={home.hero}
        locale={locale}
        heroImages={heroImages}
        photoOnly
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
