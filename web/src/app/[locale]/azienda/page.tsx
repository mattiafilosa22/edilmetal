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
import { Blueprint } from "@/components/ui/Blueprint";
import { Rail } from "@/components/ui/Rail";
import { Reveal } from "@/components/ui/Reveal";
import { SiteMap } from "@/components/ui/SiteMap";
import { CtaBand, Feats, SectionLabel, StatsRow } from "@/components/ui/blocks";

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
  const tNav = await getTranslations("Nav");

  const orari = settings.orari.map((o) => `${o.giorni} ${o.apertura}`).join(" · ");
  const sedeRows: Array<{ label: string; value: string; href?: string }> = [
    { label: t("indirizzo"), value: settings.indirizzo },
    ...(azienda.zona ? [{ label: t("zona"), value: azienda.zona }] : []),
    { label: t("telefono"), value: settings.telefono, href: `tel:${settings.telefono.replace(/\s/g, "")}` },
    { label: t("email"), value: settings.email, href: `mailto:${settings.email}` },
    ...(orari ? [{ label: t("orari"), value: orari }] : []),
  ];

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

      {/* 01 · Storia */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("storiaKick")} />
          <div>
            <div className="split2">
              <Reveal>
                <div className="sec-head">
                  <h2>{azienda.storiaTitolo}</h2>
                </div>
                {azienda.storia.map((par, i) => (
                  <p key={i} className="mt-4" style={{ color: "var(--ink-2)" }}>
                    {par}
                  </p>
                ))}
              </Reveal>
              <Reveal className="hero__draw tick">
                <Blueprint ariaLabel="Schema tecnico di un telaio in acciaio con capriata" />
              </Reveal>
            </div>
            <Reveal className="mt-8">
              <StatsRow items={azienda.stats} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 02 · Valori */}
      <section className="section section--alt">
        <div className="container sec-grid">
          <SectionLabel num="02" kick={t("valoriKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("valoriTitle")}</h2>
            </Reveal>
            <Reveal className="mt-6">
              <Feats items={azienda.valori} />
            </Reveal>
          </div>
        </div>
      </section>

      {/* 03 · Officina & team */}
      {azienda.officina.length > 0 ? (
        <section className="section">
          <div className="container sec-grid">
            <SectionLabel num="03" kick={t("officinaKick")} />
            <div>
              <Reveal className="sec-head">
                <h2>{azienda.officinaTitolo}</h2>
                {azienda.officinaSubtitle ? <p>{azienda.officinaSubtitle}</p> : null}
              </Reveal>
              <Reveal className="mt-6">
                <Rail>
                  {azienda.officina.map((item) => (
                    <div className="proj" key={item.titolo}>
                      <div className="proj__media">
                        <span className="proj__tag">{item.tag}</span>
                        <span className="ph">Foto</span>
                      </div>
                      <div className="proj__body">
                        <span className="cli">{item.cliente}</span>
                        <h3>{item.titolo}</h3>
                        <div className="meta">
                          <span>{item.luogo}</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </Rail>
              </Reveal>
            </div>
          </div>
        </section>
      ) : null}

      {/* 04 · Sede + mappa */}
      <section className="section section--alt">
        <div className="container sec-grid">
          <SectionLabel num="04" kick={t("sedeKick")} />
          <div>
            <div className="split2">
              <Reveal>
                <div className="sec-head">
                  <h2>{azienda.sedeTitolo}</h2>
                </div>
                <dl className="datalist mt-5">
                  {sedeRows.map((row) => (
                    <div key={row.label}>
                      <dt>{row.label}</dt>
                      <dd>
                        {row.href ? <a href={row.href}>{row.value}</a> : row.value}
                      </dd>
                    </div>
                  ))}
                </dl>
                {azienda.comeArrivare ? (
                  <p className="mono mt-5">{azienda.comeArrivare}</p>
                ) : null}
                <Link className="btn btn--deep mt-5" href={`/${locale}/contatti`}>
                  {t("contattaci")}
                </Link>
              </Reveal>
              <Reveal>
                <SiteMap
                  lat={settings.coordinate.lat}
                  lng={settings.coordinate.lng}
                  label={settings.indirizzo}
                  mapsUrl={settings.mapsUrl}
                />
              </Reveal>
            </div>
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="section">
        <div className="container">
          <Reveal>
            <CtaBand
              titolo={azienda.cta.titolo}
              testo={azienda.cta.testo}
              ctaLabel={tNav("ctaPreventivo")}
              ctaHref={`/${locale}/contatti`}
            />
          </Reveal>
        </div>
      </section>
    </>
  );
}
