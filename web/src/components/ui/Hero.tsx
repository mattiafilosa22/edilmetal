import Image from "next/image";
import Link from "next/link";
import type { HomeHero, Image as ImageDto } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { Blueprint } from "./Blueprint";

type HeroProps = {
  hero: HomeHero;
  locale: Locale;
  /** Foto reale dell'hero (da `settings.heroImage`); assente ⇒ fallback al disegno blueprint. */
  heroImage?: ImageDto;
  /** Credito fotografico opzionale, mostrato sotto la foto. */
  fotoCredit?: string;
};

/** Prefissa un path editoriale (relativo alla root del locale) con la locale. */
function withLocale(locale: Locale, href: string): string {
  return `/${locale}${href.startsWith("/") ? href : `/${href}`}`;
}

/**
 * Hero editoriale asimmetrico della home: testo + foto reale (o, in assenza,
 * disegno tecnico blueprint), con barra-indice mono. Un solo `h1` per pagina (WCAG).
 */
export function Hero({ hero, locale, heroImage, fotoCredit }: HeroProps) {
  return (
    <section className="hero">
      <div className="container">
        <div className="hero__grid">
          <div>
            {hero.eyebrow ? <span className="hero__eyebrow">{hero.eyebrow}</span> : null}
            <h1 className="hero__title">
              {hero.title}
              {hero.titleAccent ? (
                <>
                  {" "}
                  <em>{hero.titleAccent}</em>
                </>
              ) : null}
            </h1>
            <p className="hero__sub">{hero.subtitle}</p>
            <div className="hero__cta">
              <Link className="btn btn--deep btn--lg" href={withLocale(locale, hero.ctaPrimary.href)}>
                {hero.ctaPrimary.label}
              </Link>
              {hero.ctaSecondary ? (
                <Link
                  className="btn btn--outline btn--lg"
                  href={withLocale(locale, hero.ctaSecondary.href)}
                >
                  {hero.ctaSecondary.label}
                </Link>
              ) : null}
            </div>
          </div>
          {heroImage ? (
            <div>
              <div className="hero__photo tick">
                <Image
                  src={heroImage.src}
                  alt={heroImage.alt}
                  width={heroImage.width}
                  height={heroImage.height}
                  sizes="(max-width: 900px) 90vw, 45vw"
                  priority
                />
              </div>
              {fotoCredit ? <p className="hero__credit">{fotoCredit}</p> : null}
            </div>
          ) : (
            <div className="hero__draw tick">
              <Blueprint
                ariaLabel="Schema tecnico di un telaio in acciaio con capriata"
                withDims
              />
            </div>
          )}
        </div>
        {hero.index.length > 0 ? (
          <div className="hero__index">
            {hero.index.map((item) => (
              <div key={item.etichetta}>
                <b>{item.valore}</b>
                <span>{item.etichetta}</span>
              </div>
            ))}
          </div>
        ) : null}
      </div>
    </section>
  );
}
