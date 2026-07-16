import Image from "next/image";
import Link from "next/link";
import type { ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";

type ProjectCardProps = {
  progetto: ProgettoSummary;
  locale: Locale;
};

/**
 * Card di una realizzazione (`.proj`), usata nelle rail e nella griglia
 * portfolio. Copertina con `fill` (aspect-ratio dal contenitore, niente CLS).
 */
export function ProjectCard({ progetto, locale }: ProjectCardProps) {
  const { copertina } = progetto;
  return (
    <Link className="proj" href={`/${locale}/realizzazioni/${progetto.slug}`}>
      <div className="proj__media">
        <span className="proj__tag">{progetto.categoria.nome}</span>
        <Image
          src={copertina.src}
          alt={copertina.alt}
          fill
          sizes="(max-width: 620px) 100vw, (max-width: 1080px) 50vw, 380px"
        />
      </div>
      <div className="proj__body">
        <span className="cli">{progetto.cliente}</span>
        <h3>{progetto.titolo}</h3>
        <div className="meta">
          <span>{progetto.luogo}</span>
          <span>{progetto.anno}</span>
        </div>
      </div>
    </Link>
  );
}
