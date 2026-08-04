/**
 * Ripulitura in fase di presentazione dei testi editoriali delle realizzazioni:
 * nome del committente e anno della commessa non sono pubblici.
 *
 * Il testo nel CMS resta intatto — la rimozione avviene solo al rendering, come
 * per i campi nascosti via CSS (`.is-hidden-data`). Il confronto è
 * case-sensitive: i nomi propri sono maiuscoli, e così non si intaccano parole
 * comuni omografe ("meta", "plan", "iris"…).
 */

import type { Image, Progetto, ProgettoSummary } from "@/domain";

function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

type DatiNonPubblici = {
  cliente: string;
  titolo: string;
  anno: number;
};

/** Rimuove nomi e anno da un testo (HTML o semplice), ricucendo la punteggiatura. */
export function anonimizzaTesto(testo: string, dati: DatiNonPubblici): string {
  const nomi = [...new Set([dati.cliente, dati.titolo])].filter((n) => n.trim().length > 1);

  let out = testo;
  for (const nome of nomi) {
    const escaped = escapeRegExp(nome.trim());
    // Prima le forme con preposizione ("per Bervini"), poi il nome isolato:
    // così la frase resta leggibile invece di lasciare una preposizione orfana.
    out = out.replace(new RegExp(`\\s*\\b(per|di|presso)\\s+${escaped}\\b`, "g"), "");
    out = out.replace(new RegExp(`\\s*\\b${escaped}\\b`, "g"), "");
  }

  out = out
    .replace(new RegExp(`\\s*\\(\\s*${dati.anno}\\s*\\)`, "g"), "")
    .replace(new RegExp(`\\s*\\b${dati.anno}\\b`, "g"), "")
    // Ricuciture: parentesi svuotate, spazi doppi, spazi prima della punteggiatura.
    .replace(/\(\s*\)/g, "")
    .replace(/[ \t]{2,}/g, " ")
    .replace(/[ \t]+([,.;:!?])/g, "$1")
    .replace(/([([])\s+/g, "$1");

  return out;
}

function anonimizzaImmagine(img: Image, dati: DatiNonPubblici): Image {
  return { ...img, alt: anonimizzaTesto(img.alt, dati) };
}

/**
 * Riepilogo pronto per la UI: `cliente`, `titolo` e `anno` restano (servono ai
 * campi nascosti via CSS), mentre i testi liberi — qui il testo alternativo
 * della copertina — non citano più committente né anno.
 */
export function anonimizzaSummary(summary: ProgettoSummary): ProgettoSummary {
  return {
    ...summary,
    copertina: summary.copertina
      ? anonimizzaImmagine(summary.copertina, summary)
      : undefined,
  };
}

/** Come `anonimizzaSummary`, estesa a descrizione e galleria della scheda. */
export function anonimizzaProgetto(progetto: Progetto): Progetto {
  return {
    ...progetto,
    descrizione: anonimizzaTesto(progetto.descrizione, progetto),
    galleria: progetto.galleria.map((img) => anonimizzaImmagine(img, progetto)),
  };
}
