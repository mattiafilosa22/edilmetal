import Link from "next/link";
import type { CategoriaRef } from "@/domain";
import type { Locale } from "@/i18n/routing";

/* -------------------------------------------------------------------------- */
/* Icone                                                                       */
/* -------------------------------------------------------------------------- */

const goIcon = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" aria-hidden="true">
    <path d="M5 12h14M13 6l6 6-6 6" />
  </svg>
);

/* -------------------------------------------------------------------------- */
/* Etichetta di sezione + testata                                              */
/* -------------------------------------------------------------------------- */

export function SectionLabel({ num, kick }: { num: string; kick: string }) {
  return (
    <div className="sec-label">
      <span className="num">{num}</span>
      <span className="kick">{kick}</span>
      <span className="bar" />
    </div>
  );
}

export function SecHead({ title, sub }: { title: string; sub?: string }) {
  return (
    <div className="sec-head">
      <h2>{title}</h2>
      {sub ? <p>{sub}</p> : null}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/* Blocchi contenuto                                                           */
/* -------------------------------------------------------------------------- */

export function CategoryIndex({
  categorie,
  locale,
  showThumb = false,
}: {
  categorie: CategoriaRef[];
  locale: Locale;
  showThumb?: boolean;
}) {
  return (
    <div className="index-list">
      {categorie.map((c, i) => (
        <Link className="index-row" key={c.slug} href={`/${locale}/realizzazioni?categoria=${c.slug}`}>
          <span className="num">{String(i + 1).padStart(2, "0")}</span>
          <div>
            <h3>{c.nome}</h3>
            <p>{c.dettaglio}</p>
          </div>
          <span className="go">{goIcon}</span>
          {showThumb ? (
            <span className="thumb">
              <span className="ph">Foto</span>
            </span>
          ) : null}
        </Link>
      ))}
    </div>
  );
}
