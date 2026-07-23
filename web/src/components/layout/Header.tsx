import Image from "next/image";
import Link from "next/link";
import { getTranslations } from "next-intl/server";
import type { Locale } from "@/i18n/routing";
import type { SiteSettings } from "@/domain";
import { HeaderNav } from "./HeaderNav";

type HeaderProps = {
  locale: Locale;
  settings: SiteSettings;
};

/**
 * Header slim sticky identico su tutte le pagine: brand (logo + sottotitolo),
 * nav principale, toggle tema, switch lingua e overlay menù.
 * I recapiti dell'overlay provengono da `settings` (WordPress), non hardcoded.
 */
export async function Header({ locale, settings }: HeaderProps) {
  const t = await getTranslations("Header");
  const brandSubtitle = t("brandSubtitle");

  return (
    <header className="site-header">
      <div className="container hdr">
        <Link className="brand" href={`/${locale}`} aria-label={t("brandAria")}>
          <Image
            className="brand__img"
            src="/logo-edilmetal.png"
            alt={settings.nomeAzienda}
            width={150}
            height={38}
            priority
          />
          <span className="brand__sub">
            {brandSubtitle.split("\n").map((line, index) => (
              <span key={index}>
                {index > 0 ? <br /> : null}
                {line}
              </span>
            ))}
          </span>
        </Link>
        <HeaderNav
          locale={locale}
          phone={settings.telefono}
          email={settings.email}
          indirizzo={settings.indirizzo}
        />
      </div>
    </header>
  );
}
