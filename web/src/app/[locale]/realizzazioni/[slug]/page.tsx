import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { progettoDisplayName } from "@/domain";
import { getProgetti, getProgetto, getSettings } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  buildProjectJsonLd,
  localePath,
  localePathsFor,
  progettoPath,
} from "@/lib/seo";
import { ProjectGallery } from "@/components/ui/ProjectGallery";
import { ProjectTabs } from "@/components/ui/ProjectTabs";
import { ProjectCard } from "@/components/ui/ProjectCard";
import { RequestForm } from "@/components/ui/RequestForm";
import { Rail } from "@/components/ui/Rail";
import { Reveal } from "@/components/ui/Reveal";
import { SectionLabel } from "@/components/ui/blocks";

type PageProps = { params: Promise<{ locale: string; slug: string }> };

const RELATED_MAX = 6;

/**
 * Come si chiama pubblicamente una realizzazione: famiglia di opere e luogo.
 * Il nome del committente non è divulgabile, quindi non compare né nel titolo
 * della pagina né nei dati strutturati.
 */
function etichettaPubblica(progetto: {
  categoria: { nome: string };
  luogo: string;
}): string {
  return `${progetto.categoria.nome} — ${progetto.luogo}`;
}

export async function generateStaticParams() {
  const params: Array<{ locale: Locale; slug: string }> = [];
  for (const locale of routing.locales) {
    const progetti = await getProgetti({ locale });
    for (const p of progetti) params.push({ locale, slug: p.slug });
  }
  return params;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale, slug } = await params;
  const l = locale as Locale;
  const [progetto, tSeo] = await Promise.all([
    getProgetto({ locale: l, slug }),
    getTranslations({ locale: l, namespace: "SEO" }),
  ]);
  if (!progetto) {
    return buildMetadata({
      locale: l,
      paths: localePathsFor("realizzazioni"),
      siteName: tSeo("siteName"),
      heading: tSeo("realizzazioniHeading"),
      description: tSeo("realizzazioniDescription"),
    });
  }
  const heading = progetto.seo?.title ?? etichettaPubblica(progetto);
  const description =
    progetto.seo?.description ??
    progetto.descrizione.slice(0, 160);
  const cover = progetto.galleria[0];
  return buildMetadata({
    locale: l,
    paths: localePathsFor("realizzazioni", slug),
    siteName: tSeo("siteName"),
    heading,
    description,
    ogType: "article",
    image: cover
      ? {
          url: absoluteUrl(progetto.seo?.ogImage ?? cover.src),
          width: cover.width,
          height: cover.height,
          alt: cover.alt,
        }
      : undefined,
  });
}

export default async function SchedaProgettoPage({ params }: PageProps) {
  const { locale: rawLocale, slug } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const progetto = await getProgetto({ locale, slug });
  if (!progetto) notFound();

  const [all, t] = await Promise.all([
    getProgetti({ locale, filters: { categoria: progetto.categoria.slug } }),
    getTranslations("Scheda"),
  ]);
  const settings = await getSettings({ locale });

  const correlati = all.filter((p) => p.slug !== progetto.slug).slice(0, RELATED_MAX);

  const projectJsonLd = buildProjectJsonLd({
    progetto,
    name: etichettaPubblica(progetto),
    url: absoluteUrl(progettoPath(locale, slug)),
    images: progetto.galleria.map((img) => absoluteUrl(img.src)),
    creatorName: settings.nomeAzienda,
    creatorUrl: absoluteUrl(localePath(locale)),
  });
  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: t("breadcrumbRealizzazioni"), url: absoluteUrl(localePath(locale, "realizzazioni")) },
    { name: progetto.categoria.nome, url: absoluteUrl(progettoPath(locale, slug)) },
  ]);

  // `hidden`: dato presente nella scheda ma non pubblico (committente e anno).
  const databox: Array<{ label: string; value: string; hidden?: boolean }> = [
    { label: t("categoria"), value: progetto.categoria.nome },
    { label: t("cliente"), value: progetto.cliente, hidden: true },
    ...(progetto.settore ? [{ label: t("settore"), value: progetto.settore }] : []),
    { label: t("luogo"), value: progetto.luogo },
    { label: t("anno"), value: String(progetto.anno), hidden: true },
  ];

  return (
    <>
      <JsonLd data={[projectJsonLd, breadcrumbJsonLd]} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <Link href={`/${locale}/realizzazioni`}>{t("breadcrumbRealizzazioni")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">
              {progetto.categoria.nome}
              <span className="is-hidden-data"> — {progettoDisplayName(progetto)}</span>
            </span>
          </nav>
          <div className="sp-head mt-4">
            <div>
              <h1>
                {progetto.categoria.nome}
                <span className="is-hidden-data"> — {progettoDisplayName(progetto)}</span>
              </h1>
              <div className="sp-head__meta">
                {progetto.settore ? (
                  <span className="sp-tag">{t("settorePrefix")} {progetto.settore.toLowerCase()}</span>
                ) : null}
                <span className="sp-tag">{progetto.luogo}</span>
                <span className="sp-tag is-hidden-data">{progetto.anno}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="section" style={{ paddingBlock: "var(--sp-8)" }}>
        <div className="container">
          <div className="sp-layout">
            <div>
              <ProjectGallery images={progetto.galleria} fallbackAlt={etichettaPubblica(progetto)} />
              <ProjectTabs
                descrizione={progetto.descrizione}
                datiTecnici={progetto.datiTecnici}
                lavorazioni={progetto.lavorazioni}
                materiali={progetto.materiali}
              />
            </div>

            <aside className="databox">
              <h2>{t("schedaTitle")}</h2>
              <dl className="datalist">
                {databox.map((row) => (
                  <div key={row.label} className={row.hidden ? "is-hidden-data" : undefined}>
                    <dt>{row.label}</dt>
                    <dd>{row.value}</dd>
                  </div>
                ))}
              </dl>
              <a className="btn btn--deep btn--lg" href="#simile" style={{ width: "100%", marginTop: "var(--sp-5)" }}>
                {t("ctaSimile")}
              </a>
            </aside>
          </div>
        </div>
      </section>

      <section className="section section--alt" id="simile">
        <div className="container sec-grid">
          <SectionLabel num="02" kick={t("richiestaKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("richiestaTitle")}</h2>
              <p>{t("richiestaSub")}</p>
            </Reveal>
            <Reveal className="mt-6">
              <RequestForm
                compact
                categorie={[progetto.categoria]}
                locale={locale}
                defaultTipoOpera={progetto.categoria.slug}
                progettoSlug={progetto.slug}
              />
            </Reveal>
          </div>
        </div>
      </section>

      {correlati.length > 0 ? (
        <section className="section section--rail">
          <div className="container">
            <Reveal className="rail-head">
              <div>
                <span className="kicker">
                  <span className="num">03</span>
                  <span className="txt">{t("correlatiKick")}</span>
                </span>
                <h2>{t("correlatiTitle")}</h2>
                <p>{t("correlatiSub")}</p>
              </div>
              <Link className="link-mono" href={`/${locale}/realizzazioni`}>
                {t("tutteLeRealizzazioni")}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                  <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
              </Link>
            </Reveal>
            <Reveal>
              <Rail>
                {correlati.map((p) => (
                  <ProjectCard key={p.id} progetto={p} locale={locale} />
                ))}
              </Rail>
            </Reveal>
          </div>
        </section>
      ) : null}
    </>
  );
}
