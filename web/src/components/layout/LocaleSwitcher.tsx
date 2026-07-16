"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useLocale, useTranslations } from "next-intl";
import { routing, type Locale } from "@/i18n/routing";

/**
 * Switch lingua (predisposizione multilingua; oggi solo IT pubblicata).
 * Tutte le route condividono gli stessi segmenti tra le lingue — inclusi gli
 * slug delle realizzazioni, che sono nomi propri/tecnici invarianti — quindi
 * basta sostituire il segmento di locale iniziale.
 */
export function LocaleSwitcher() {
  const t = useTranslations("LangSwitch");
  const active = useLocale();
  const pathname = usePathname();

  function hrefFor(locale: Locale): string {
    if (locale === active) return pathname;
    const segments = pathname.split("/");
    if (segments.length > 1) {
      segments[1] = locale;
      return segments.join("/") || `/${locale}`;
    }
    return `/${locale}`;
  }

  return (
    <div className="lang-switch" role="group" aria-label={t("label")}>
      {routing.locales.map((locale) => (
        <Link
          key={locale}
          href={hrefFor(locale)}
          hrefLang={locale}
          aria-current={locale === active ? "true" : undefined}
        >
          {t(locale)}
        </Link>
      ))}
    </div>
  );
}
