import Link from "next/link";
import type { ReactNode } from "react";
import type { CategoriaRef, Feature, Stat, Step } from "@/domain";
import type { Locale } from "@/i18n/routing";

/* -------------------------------------------------------------------------- */
/* Icone feature (ciclate per indice)                                          */
/* -------------------------------------------------------------------------- */

const FEAT_ICONS: ReactNode[] = [
  <g key="hex">
    <path d="M12 2 3 7v10l9 5 9-5V7z" />
    <path d="M12 22V12M3 7l9 5 9-5" />
  </g>,
  <g key="doc">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
    <path d="M14 2v6h6M9 13l2 2 4-4" />
  </g>,
  <g key="building">
    <path d="M3 21h18M6 21V9l6-4 6 4v12M9 21v-6h6v6" />
  </g>,
  <g key="refresh">
    <path d="M20.6 8.5A9 9 0 1 0 21 12M22 4l-6 6-3-3" />
  </g>,
];

function FeatIcon({ index, className = "ico" }: { index: number; className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
      {FEAT_ICONS[index % FEAT_ICONS.length]}
    </svg>
  );
}

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

export function StatsRow({ items }: { items: Stat[] }) {
  return (
    <div className="stats">
      {items.map((s) => (
        <div className="stat" key={s.etichetta}>
          <div className="n">{s.valore}</div>
          <div className="l">{s.etichetta}</div>
        </div>
      ))}
    </div>
  );
}

export function Flow({ steps }: { steps: Step[] }) {
  return (
    <div className="flow">
      {steps.map((s) => (
        <div className="flow__step" key={s.titolo}>
          <h3>{s.titolo}</h3>
          <p>{s.testo}</p>
        </div>
      ))}
    </div>
  );
}

export function Feats({ items }: { items: Feature[] }) {
  return (
    <div className="feats">
      {items.map((f, i) => (
        <div className="feat" key={f.titolo}>
          <FeatIcon index={i} />
          <h3>{f.titolo}</h3>
          <p>{f.testo}</p>
        </div>
      ))}
    </div>
  );
}

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
        <Link className="index-row" key={c.slug} href={`/${locale}/realizzazioni`}>
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

export function Clients({ items }: { items: string[] }) {
  return (
    <div className="clients">
      {items.map((name) => (
        <span key={name}>{name}</span>
      ))}
    </div>
  );
}

export function Callout({ titolo, testo }: { titolo: string; testo: string }) {
  return (
    <div className="callout">
      <FeatIcon index={1} />
      <div>
        <h3>{titolo}</h3>
        <p>{testo}</p>
      </div>
    </div>
  );
}

export function CtaBand({
  titolo,
  testo,
  ctaLabel,
  ctaHref,
}: {
  titolo: string;
  testo: string;
  ctaLabel: string;
  ctaHref: string;
}) {
  return (
    <div className="cta-band">
      <div>
        <h2>{titolo}</h2>
        <p>{testo}</p>
      </div>
      <Link className="btn btn--accent btn--lg" href={ctaHref}>
        {ctaLabel}
      </Link>
    </div>
  );
}
