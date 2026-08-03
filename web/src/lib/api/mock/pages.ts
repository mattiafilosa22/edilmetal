import type { CategoriaRef, PageContent } from "@/domain";
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
      ctaSecondary: { label: "Richiedi un preventivo", href: "/contatti" },
      index: [
        { valore: "Dal 1997", etichetta: "Esperienza in cantiere" },
        { valore: "Su commessa", etichetta: "Calcoli firmati da tecnici abilitati" },
        { valore: "Noceto (PR)", etichetta: "Progettazione · produzione · montaggio" },
      ],
    },
    inEvidenza: [
      {
        categoria: { slug: "strutture-acciaio", nome: "Strutture in acciaio" },
        immagine: {
          src: "/placeholder-progetto.svg",
          width: 1200,
          height: 900,
          alt: "Struttura in acciaio per capannone industriale",
        },
      },
      {
        categoria: { slug: "pensiline", nome: "Pensiline" },
        immagine: {
          src: "/placeholder-progetto.svg",
          width: 1200,
          height: 900,
          alt: "Pensilina industriale in acciaio",
        },
      },
    ],
  },
};

const servizi: PageContent = {
  key: "servizi",
  title: "Cosa facciamo",
  subtitle:
    "Progettazione, costruzione e montaggio di strutture in carpenteria metallica per l'edilizia industriale, commerciale e terziaria. Un unico interlocutore, dal sopralluogo al post-vendita.",
  servizi: {
    tipologie: CATEGORIE,
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
  },
};

const contatti: PageContent = {
  key: "contatti",
  title: "Richiedi un preventivo",
  subtitle:
    "Raccontaci la tua opera in carpenteria metallica: ti ricontattiamo con una prima valutazione e un preventivo dedicato.",
  contatti: {},
};

const privacyPolicy: PageContent = {
  key: "privacy-policy",
  title: "Privacy Policy",
  legal: {
    body: "<p>La presente informativa descrive le modalità di trattamento dei dati personali degli utenti che consultano il sito e utilizzano i moduli di contatto, ai sensi del Regolamento UE 2016/679 (GDPR).</p>",
    updatedAt: "2026-01-01",
  },
};

const cookiePolicy: PageContent = {
  key: "cookie-policy",
  title: "Cookie Policy",
  legal: {
    body: "<p>Questo sito utilizza solo cookie tecnici necessari al funzionamento.</p>",
    updatedAt: "2026-01-01",
  },
};

const itPages: Record<string, PageContent> = {
  home,
  servizi,
  azienda,
  contatti,
  "privacy-policy": privacyPolicy,
  "cookie-policy": cookiePolicy,
};

export const mockPages: Record<Locale, Record<string, PageContent>> = {
  it: itPages,
  // Predisposizione multilingua: l'inglese ricade sui contenuti IT finché non
  // vengono tradotti in WordPress.
  en: itPages,
};
