import Link from "next/link";
import { hasRealPhoto, type ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { ProjectCard } from "./ProjectCard";
import { Rail } from "./Rail";
import { Reveal } from "./Reveal";

type RealizzazioniBandProps = {
  progetti: ProgettoSummary[];
  locale: Locale;
  kick: string;
  title: string;
  subtitle: string;
  ctaLabel: string;
  ctaHref: string;
  /** Numero di sezione mostrato nel kicker (default "02"). */
  num?: string;
};

const rightArrow = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
    <path d="M5 12h14M13 6l6 6-6 6" />
  </svg>
);

/**
 * Banda scura "Realizzazioni" (blocco storico del sito, restyle blueprint):
 * rail delle realizzazioni in evidenza su sfondo `--deep`.
 */
export function RealizzazioniBand({
  progetti,
  locale,
  kick,
  title,
  subtitle,
  ctaLabel,
  ctaHref,
  num = "02",
}: RealizzazioniBandProps) {
  const withPhoto = progetti.filter(hasRealPhoto);

  if (withPhoto.length === 0) {
    return null;
  }

  return (
    <section className="section section--rail realiz-band">
      <div className="container">
        <Reveal className="rail-head">
          <div>
            <span className="kicker">
              <span className="num">{num}</span>
              <span className="txt">{kick}</span>
            </span>
            <h2>{title}</h2>
            <p>{subtitle}</p>
          </div>
          <Link className="link-mono" href={ctaHref}>
            {ctaLabel} {rightArrow}
          </Link>
        </Reveal>
        <Reveal>
          <Rail>
            {withPhoto.map((p) => (
              <ProjectCard key={p.id} progetto={p} locale={locale} />
            ))}
          </Rail>
        </Reveal>
      </div>
    </section>
  );
}
