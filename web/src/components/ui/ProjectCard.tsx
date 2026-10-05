import Image from "next/image";
import Link from "next/link";
import { progettoDisplayName, type ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";

type ProjectCardProps = {
  progetto: ProgettoSummary;
  locale: Locale;
};

/**
 * Card di una realizzazione (`.proj`), usata nelle rail e nella griglia
 * portfolio. Copertina con `fill` (aspect-ratio dal contenitore, niente CLS).
 *
 * Nome del committente e anno non sono pubblici (vedi `.is-hidden-data` in
 * `pages.css`): restano nel markup — così il link conserva un nome accessibile
 * univoco — ma non a schermo. Il titolo visibile è la famiglia di opere.
 */
export function ProjectCard({ progetto, locale }: ProjectCardProps) {
  const { copertina } = progetto;
  return (
    <Link className="proj" href={`/${locale}/realizzazioni/${progetto.slug}`}>
      <div className="proj__media">
        <Image
          src={copertina?.src ?? "/placeholder-progetto.svg"}
          alt={copertina?.alt ?? progetto.titolo}
          fill
          sizes="(max-width: 620px) 100vw, (max-width: 1080px) 50vw, 380px"
        />
      </div>
      <div className="proj__body">
        <span className="is-hidden-data cli">{progettoDisplayName(progetto)}</span>
        <h3>{progetto.categoria.nome}</h3>
        <div className="meta">
          <span className="is-hidden-data">{progetto.anno}</span>
        </div>
      </div>
    </Link>
  );
}
