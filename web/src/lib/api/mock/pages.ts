import type { CategoriaRef, Feature, PageContent, Step } from "@/domain";
import type { Locale } from "@/i18n/routing";

/**
 * Contenuti editoriali demo. In produzione arrivano da
 * `wp-json/edilmetal/v1/pages/{key}` (Polylang gestisce le traduzioni).
 * Una sola lingua (IT) è pubblicata; l'inglese ricade sull'italiano finché
 * non vengono forniti i testi tradotti (`getPage` gestisce il fallback).
 */

/** Le 8 famiglie di opere (indice condiviso home/servizi). */
const CATEGORIE: CategoriaRef[] = [
  { slug: "strutture-acciaio", nome: "Strutture in acciaio", dettaglio: "Capannoni · soppalchi · edifici industriali" },
  { slug: "strutture-miste", nome: "Strutture miste", dettaglio: "Acciaio-calcestruzzo · ampliamenti" },
  { slug: "scale", nome: "Scale", dettaglio: "Interne · esterne · di sicurezza" },
  { slug: "pensiline", nome: "Pensiline", dettaglio: "Industriali · di ingresso · di carico" },
  { slug: "pensiline-auto", nome: "Pensiline auto / carport", dettaglio: "Aree di sosta · fotovoltaico" },
  { slug: "coperture-tamponamenti", nome: "Coperture e tamponamenti", dettaglio: "Pannelli · lamiere · isolamento" },
  { slug: "rivestimenti-facciata", nome: "Rivestimenti di facciata", dettaglio: "Frangisole · lamiere forate · finiture" },
  { slug: "opere-speciali", nome: "Opere speciali", dettaglio: "Su disegno · carpenteria di dettaglio" },
];

/** Le 4 fasi della commessa. */
const PROCESSO: Step[] = [
  { titolo: "Sopralluogo & progetto", testo: "Rilievo in loco, consulenza rapida e progettazione dedicata sulla commessa." },
  { titolo: "Calcolo strutturale", testo: "Relazioni di calcolo firmate da tecnici abilitati e iscritti agli albi." },
  { titolo: "Produzione", testo: "Taglio, saldatura, trattamenti e finiture nella nostra officina." },
  { titolo: "Montaggio", testo: "Posa in cantiere con squadra interna e assistenza post-vendita." },
];

/** I 4 punti di forza. */
const PERCHE: Feature[] = [
  { titolo: "Un solo interlocutore", testo: "Progettazione, produzione e montaggio gestiti internamente, senza rimpalli." },
  { titolo: "Calcoli firmati", testo: "Relazioni di calcolo redatte e sottoscritte da tecnici abilitati e iscritti agli albi." },
  { titolo: "Su misura", testo: "Ogni opera è progettata sulla commessa e sul contesto del cliente." },
  { titolo: "Post-vendita", testo: "Consulenza rapida e assistenza anche dopo la consegna." },
];

const home: PageContent = {
  key: "home",
  title: "Edilmetal · Carpenteria metallica su commessa",
  subtitle:
    "Progettazione, costruzione e montaggio di strutture in acciaio dal 1997.",
  home: {
    hero: {
      eyebrow: "Carpenteria metallica · su commessa · dal 1997",
      title: "Progettiamo strutture in acciaio,",
      titleAccent: "su misura.",
      subtitle:
        "Dal sopralluogo alle relazioni di calcolo firmate, fino al montaggio in cantiere e al post-vendita. Un unico interlocutore per l'edilizia industriale, commerciale e terziaria.",
      ctaPrimary: { label: "Le realizzazioni", href: "/realizzazioni" },
      ctaSecondary: { label: "Richiedi un preventivo →", href: "/contatti" },
      index: [
        { valore: "Dal 1997", etichetta: "Esperienza in cantiere" },
        { valore: "Su commessa", etichetta: "Calcoli firmati da tecnici abilitati" },
        { valore: "Noceto (PR)", etichetta: "Progettazione · produzione · montaggio" },
      ],
    },
    statsIntro: {
      titolo: "Numeri che tengono.",
      testo:
        "Nata nel 1997 dall'incontro di Alessio Ricci e Aldo Medioli, Edilmetal costruisce e monta carpenteria metallica in tutta Italia.",
    },
    stats: [
      { valore: "27+", etichetta: "Anni di attività" },
      { valore: "500+", etichetta: "Opere realizzate" },
      { valore: "8", etichetta: "Famiglie di opere" },
      { valore: "100%", etichetta: "Calcoli firmati" },
    ],
    categorie: CATEGORIE,
    processo: PROCESSO,
    perche: PERCHE,
    referenze: ["Parmalat", "Italbox", "Bervini", "Iris", "Aiassa", "Meta", "+ molti altri"],
    cta: {
      titolo: "Hai una struttura in mente?",
      testo: "Raccontaci la commessa: ti rispondiamo con un preventivo.",
    },
  },
};

const servizi: PageContent = {
  key: "servizi",
  title: "Cosa facciamo",
  subtitle:
    "Progettazione, costruzione e montaggio di strutture in carpenteria metallica per l'edilizia industriale, commerciale e terziaria. Un unico interlocutore, dal sopralluogo al post-vendita.",
  servizi: {
    processo: PROCESSO,
    tipologie: CATEGORIE,
    vantaggi: PERCHE,
    callout: {
      titolo: "Relazioni di calcolo firmate",
      testo:
        "Ogni struttura è accompagnata da relazioni di calcolo redatte secondo NTC 2018 e firmate da tecnici abilitati iscritti agli albi: la garanzia documentale che l'opera è verificata e a norma.",
    },
    cta: {
      titolo: "Hai una commessa da valutare?",
      testo: "Raccontaci l'opera: ti rispondiamo con un preventivo.",
    },
  },
};

const azienda: PageContent = {
  key: "azienda",
  title: "Carpenteria metallica dal 1997",
  subtitle:
    "Nata dall'incontro di due mestieri, Edilmetal costruisce e monta strutture in acciaio su commessa per l'industria, il commercio e il terziario.",
  azienda: {
    storiaTitolo: "Dal 1997, su commessa.",
    storia: [
      "Edilmetal S.r.l. nasce nel 1997 dall'iniziativa di Alessio Ricci e Aldo Medioli, con una vocazione precisa: la costruzione e il montaggio di strutture in carpenteria metallica per l'edilizia industriale, commerciale e terziaria, sia in nuova costruzione sia in ristrutturazione.",
      "Lavoriamo su progettazione dedicata per ogni cliente, dalla consulenza rapida alla preventivazione, fino alle relazioni di calcolo firmate da tecnici abilitati, al montaggio in cantiere e al post-vendita. Negli anni ci hanno scelto realtà anche prestigiose come Parmalat, Italbox, Bervini e Iris.",
    ],
    stats: [
      { valore: "1997", etichetta: "Anno di fondazione" },
      { valore: "500+", etichetta: "Opere realizzate" },
      { valore: "8", etichetta: "Famiglie di opere" },
      { valore: "4,5★", etichetta: "Recensioni Google" },
    ],
    valori: [
      { titolo: "Rigore tecnico", testo: "Calcoli firmati e opere verificate secondo normativa: nessuna scorciatoia." },
      { titolo: "Responsabilità unica", testo: "Un solo referente dalla progettazione al montaggio, per tempi certi." },
      { titolo: "Su misura", testo: "Ogni commessa è progettata sul cliente e sul contesto del cantiere." },
      { titolo: "Relazione nel tempo", testo: "Assistenza post-vendita e disponibilità anche dopo la consegna." },
    ],
    officinaTitolo: "Dentro l'officina.",
    officinaSubtitle:
      "Produzione interna: taglio, saldatura certificata, trattamenti e finiture.",
    officina: [
      { tag: "Officina", cliente: "Produzione", titolo: "Area saldatura", luogo: "Noceto (PR)" },
      { tag: "Officina", cliente: "Produzione", titolo: "Taglio e assemblaggio", luogo: "Noceto (PR)" },
      { tag: "Team", cliente: "Squadra", titolo: "Montaggio in cantiere", luogo: "In trasferta" },
      { tag: "Officina", cliente: "Produzione", titolo: "Trattamenti e finiture", luogo: "Noceto (PR)" },
    ],
    sedeTitolo: "La sede.",
    zona: "Ponte Taro · Provincia di Parma",
    comeArrivare:
      "Come raggiungerci — uscita A15 Parma Ovest / SS9 Via Emilia, a pochi minuti dal casello.",
    cta: {
      titolo: "Lavoriamo insieme?",
      testo: "Raccontaci la commessa: ti rispondiamo con un preventivo.",
    },
  },
};

const contatti: PageContent = {
  key: "contatti",
  title: "Richiedi un preventivo",
  subtitle:
    "Raccontaci la tua opera in carpenteria metallica: ti ricontattiamo con una prima valutazione e un preventivo dedicato.",
  contatti: {},
};

const itPages: Record<string, PageContent> = {
  home,
  servizi,
  azienda,
  contatti,
};

export const mockPages: Record<Locale, Record<string, PageContent>> = {
  it: itPages,
  // Predisposizione multilingua: l'inglese ricade sui contenuti IT finché non
  // vengono tradotti in WordPress.
  en: itPages,
};
