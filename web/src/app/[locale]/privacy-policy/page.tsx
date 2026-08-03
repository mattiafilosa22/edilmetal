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
import { LegalContent } from "@/components/ui/LegalContent";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("privacy-policy"),
    siteName: t("siteName"),
    heading: t("privacyPolicyHeading"),
    description: t("privacyPolicyDescription"),
  });
}

export default async function PrivacyPolicyPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const page = await getPage({ locale, key: "privacy-policy" });
  const legal = page?.legal;
  if (!page || !legal) notFound();

  const t = await getTranslations("Legal");

  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: page.title, url: absoluteUrl(localePath(locale, "privacy-policy")) },
  ]);

  const updatedAtLabel = legal.updatedAt
    ? t("updatedAt", {
        date: new Intl.DateTimeFormat(locale, { dateStyle: "long" }).format(new Date(legal.updatedAt)),
      })
    : undefined;

  return (
    <>
      <JsonLd data={breadcrumbJsonLd} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">{page.title}</span>
          </nav>
          <h1>{page.title}.</h1>
        </div>
      </section>

      <LegalContent body={legal.body} updatedAtLabel={updatedAtLabel} />
    </>
  );
}
