import type { Metadata } from "next";
import Link from "next/link";
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
import { Rail } from "@/components/ui/Rail";
import { ProjectCard } from "@/components/ui/ProjectCard";
import { Reveal } from "@/components/ui/Reveal";
import {
  CategoryIndex,
  Clients,
  CtaBand,
  Feats,
  Flow,
  SectionLabel,
  StatsRow,
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
    paths: localePathsFor(),
    siteName: t("siteName"),
    heading: t("homeHeading"),
    description: t("homeDescription"),
  });
}

const rightArrow = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
    <path d="M5 12h14M13 6l6 6-6 6" />
  </svg>
);

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

      <Hero hero={home.hero} locale={locale} />

      {/* 00 · Azienda (stat) */}
      <section className="section section--alt">
        <div className="container sec-grid">
          <SectionLabel num="00" kick={t("aziendaKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{home.statsIntro.titolo}</h2>
              <p>{home.statsIntro.testo}</p>
            </Reveal>
            <Reveal className="mt-7">
              <StatsRow items={home.stats} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 01 · Cosa realizziamo (indice numerato) */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("cosaKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("cosaTitle")}</h2>
              <p>{t("cosaSub")}</p>
            </Reveal>
            <Reveal className="mt-6">
              <CategoryIndex categorie={home.categorie} locale={locale} showThumb />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 02 · Processo (flow) */}
      <section className="section section--alt">
        <div className="container sec-grid">
          <SectionLabel num="02" kick={t("processoKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("processoTitle")}</h2>
              <p>{t("processoSub")}</p>
            </Reveal>
            <Reveal className="mt-8">
              <Flow steps={home.processo} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 03 · Realizzazioni recenti (rail) */}
      {evidenza.length > 0 ? (
        <section className="section section--rail">
          <div className="container">
            <Reveal className="rail-head">
              <div>
                <span className="kicker">
                  <span className="num">03</span>
                  <span className="txt">{t("evidenzaKick")}</span>
                </span>
                <h2>{t("evidenzaTitle")}</h2>
                <p>{t("evidenzaSub")}</p>
              </div>
              <Link className="link-mono" href={`/${locale}/realizzazioni`}>
                {t("tutteLeRealizzazioni")} {rightArrow}
              </Link>
            </Reveal>
            <Reveal>
              <Rail>
                {evidenza.map((p) => (
                  <ProjectCard key={p.id} progetto={p} locale={locale} />
                ))}
              </Rail>
            </Reveal>
          </div>
        </section>
      ) : null}

      {/* 04 · Perché (feats) */}
      <section className="section section--alt">
        <div className="container sec-grid">
          <SectionLabel num="04" kick={t("percheKick")} />
          <Reveal className="feats-wrap">
            <Feats items={home.perche} />
          </Reveal>
        </div>
      </section>

      {/* 05 · Referenze */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="05" kick={t("referenzeKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("referenzeTitle")}</h2>
              <p>{t("referenzeSub")}</p>
            </Reveal>
            <Reveal className="mt-6">
              <Clients items={home.referenze} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <Reveal>
            <CtaBand
              titolo={home.cta.titolo}
              testo={home.cta.testo}
              ctaLabel={home.hero.ctaSecondary?.label ?? t("tutteLeRealizzazioni")}
              ctaHref={`/${locale}/contatti`}
            />
          </Reveal>
        </div>
      </section>
    </>
  );
}
