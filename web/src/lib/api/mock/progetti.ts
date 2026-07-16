import type {
  CategoriaSlug,
  DatoTecnico,
  Image,
  Progetto,
} from "@/domain";

/**
 * Dataset demo usato quando WordPress non è disponibile (build senza CMS).
 * Rispecchia realizzazioni reali di carpenteria metallica con clienti citati
 * nel brief (Parmalat, Italbox, Iris, Bervini, Aiassa, Meta, Castellazzo,
 * Pinko, Ricci). Le immagini sono placeholder "blueprint" finché non arrivano
 * le foto reali dal CMS. I dati rispettano gli schemi zod del dominio.
 */

/** Nome visualizzato di ogni famiglia di opere (tassonomia `categoria_opera`). */
const CATEGORIA_NOME: Record<CategoriaSlug, string> = {
  "strutture-acciaio": "Strutture in acciaio",
  "strutture-miste": "Strutture miste",
  scale: "Scale",
  pensiline: "Pensiline",
  "pensiline-auto": "Pensiline auto / carport",
  "coperture-tamponamenti": "Coperture e tamponamenti",
  "rivestimenti-facciata": "Rivestimenti di facciata",
  "opere-speciali": "Opere speciali",
};

/** Galleria placeholder: una foto per ogni fase, con alt descrittivo. */
function galleria(titolo: string): Image[] {
  const fasi = [
    "Vista d'insieme",
    "Dettaglio nodo trave-pilastro",
    "Fase di montaggio in cantiere",
    "Dettaglio delle finiture",
  ];
  return fasi.map((fase) => ({
    src: "/placeholder-progetto.svg",
    width: 1200,
    height: 800,
    alt: `${titolo} — ${fase.toLowerCase()}`,
  }));
}

type RealProgetto = {
  id: string;
  slug: string;
  titolo: string;
  cliente: string;
  luogo: string;
  anno: number;
  categoria: CategoriaSlug;
  settore: string;
  inEvidenza: boolean;
  descrizione: string;
  datiTecnici: DatoTecnico[];
  lavorazioni: string[];
  materiali: string[];
};

/** Lavorazioni e materiali ricorrenti (variazioni per tipo di opera). */
const LAVORAZIONI_STD = [
  "Taglio plasma e sega a nastro CNC",
  "Saldatura MIG/MAG certificata EN 1090",
  "Sabbiatura SA 2.5 e zincatura a caldo",
  "Verniciatura a polvere RAL a campione",
  "Montaggio in cantiere con squadra interna",
];
const MATERIALI_STD = [
  "Profili HEA/IPE in acciaio S275JR",
  "Controventi in tondi e piatti S355",
  "Bulloneria classe 8.8 zincata",
];

const real: RealProgetto[] = [
  {
    id: "1",
    slug: "pensilina-industriale-parmalat",
    titolo: "Pensilina industriale",
    cliente: "Parmalat",
    luogo: "Collecchio (PR)",
    anno: 2023,
    categoria: "pensiline",
    settore: "Industriale",
    inEvidenza: true,
    descrizione:
      "Copertura di carico e scarico su commessa, progettata sul layout logistico dello stabilimento. La geometria a sbalzo libera completamente la corsia dei mezzi, senza pilastri intermedi. Intervento gestito da Edilmetal come unico interlocutore: sopralluogo, progettazione, relazioni di calcolo firmate, produzione e montaggio.",
    datiTecnici: [
      { label: "Luce libera", valore: "24,00 m" },
      { label: "Altezza", valore: "8,00 m" },
      { label: "Superficie coperta", valore: "620 m²" },
      { label: "Tonnellaggio acciaio", valore: "~ 42 t" },
      { label: "Classe d'uso", valore: "CU II · NTC 2018" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [...MATERIALI_STD, "Copertura in lamiera grecata coibentata"],
  },
  {
    id: "2",
    slug: "capannone-industriale-italbox",
    titolo: "Capannone industriale",
    cliente: "Italbox",
    luogo: "Parma",
    anno: 2022,
    categoria: "strutture-acciaio",
    settore: "Industriale",
    inEvidenza: true,
    descrizione:
      "Nuovo capannone produttivo in struttura d'acciaio a campata unica, dimensionato per accogliere le linee di lavorazione del cliente. Telaio a portale con controventi di falda e di parete, calcolato secondo NTC 2018 e consegnato chiavi in mano.",
    datiTecnici: [
      { label: "Superficie", valore: "2.400 m²" },
      { label: "Luce", valore: "30,00 m" },
      { label: "Altezza sottotrave", valore: "9,00 m" },
      { label: "Tonnellaggio acciaio", valore: "~ 180 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: MATERIALI_STD,
  },
  {
    id: "3",
    slug: "rivestimento-facciata-iris",
    titolo: "Rivestimento di facciata",
    cliente: "Iris",
    luogo: "Reggio Emilia",
    anno: 2021,
    categoria: "rivestimenti-facciata",
    settore: "Industriale",
    inEvidenza: true,
    descrizione:
      "Sistema di rivestimento di facciata in lamiera microforata su sottostruttura metallica, disegnato per unificare l'immagine dello stabilimento e schermare gli impianti tecnologici. Posa su staffe regolabili per assorbire le tolleranze dell'esistente.",
    datiTecnici: [
      { label: "Superficie facciata", valore: "1.150 m²" },
      { label: "Sviluppo", valore: "95,00 m" },
      { label: "Tonnellaggio acciaio", valore: "~ 24 t" },
    ],
    lavorazioni: [
      "Taglio laser e calandratura",
      "Foratura CNC dei pannelli",
      "Zincatura a caldo e verniciatura RAL",
      "Montaggio su staffe regolabili",
    ],
    materiali: [
      "Sottostruttura in profili tubolari S275",
      "Lamiera microforata in acciaio zincato",
      "Staffe regolabili in acciaio inox",
    ],
  },
  {
    id: "4",
    slug: "scala-sicurezza-bervini",
    titolo: "Scala di sicurezza",
    cliente: "Bervini",
    luogo: "Noceto (PR)",
    anno: 2020,
    categoria: "scale",
    settore: "Commerciale",
    inEvidenza: true,
    descrizione:
      "Scala di sicurezza esterna a più rampe con pianerottoli intermedi, progettata per l'esodo di emergenza. Struttura autoportante ancorata al fabbricato, con parapetti a norma e trattamento anticorrosione a lunga durata.",
    datiTecnici: [
      { label: "Sviluppo verticale", valore: "12,50 m" },
      { label: "Rampe", valore: "4" },
      { label: "Larghezza utile", valore: "1,20 m" },
      { label: "Tonnellaggio acciaio", valore: "~ 6 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [
      "Profili in acciaio S275JR",
      "Gradini in grigliato elettroforgiato zincato",
      "Parapetti in tubolare con corrimano",
    ],
  },
  {
    id: "5",
    slug: "ampliamento-produttivo-aiassa",
    titolo: "Ampliamento produttivo",
    cliente: "Aiassa",
    luogo: "Parma",
    anno: 2024,
    categoria: "strutture-miste",
    settore: "Industriale",
    inEvidenza: false,
    descrizione:
      "Ampliamento di un'area produttiva con struttura mista acciaio-calcestruzzo: pilastri in c.a. esistenti integrati da un telaio d'acciaio per la nuova campata e il soppalco tecnico. Cantiere organizzato per non interrompere la produzione.",
    datiTecnici: [
      { label: "Superficie ampliamento", valore: "780 m²" },
      { label: "Soppalco", valore: "260 m²" },
      { label: "Tonnellaggio acciaio", valore: "~ 55 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [...MATERIALI_STD, "Lamiera grecata collaborante per il soppalco"],
  },
  {
    id: "6",
    slug: "scala-scenografica-meta",
    titolo: "Scala scenografica",
    cliente: "Meta",
    luogo: "Reggio Emilia",
    anno: 2023,
    categoria: "opere-speciali",
    settore: "Terziario",
    inEvidenza: false,
    descrizione:
      "Scala interna a giorno per uno spazio direzionale: struttura in acciaio a sbalzo con gradini a mensola e parapetto in vetro strutturale. Carpenteria di dettaglio con saldature a vista molate e finitura di alta qualità.",
    datiTecnici: [
      { label: "Sviluppo", valore: "5,40 m" },
      { label: "Gradini a sbalzo", valore: "18" },
      { label: "Finitura", valore: "Verniciatura a polvere goffrata" },
    ],
    lavorazioni: [
      "Taglio laser e piegatura di precisione",
      "Saldatura a vista molata e lucidata",
      "Verniciatura a polvere goffrata",
      "Montaggio con posa del parapetto in vetro",
    ],
    materiali: [
      "Cosciali in lamiera S355 piegata",
      "Gradini in lamiera bugnata",
      "Parapetto in vetro stratificato di sicurezza",
    ],
  },
  {
    id: "7",
    slug: "copertura-maneggio-castellazzo",
    titolo: "Copertura maneggio",
    cliente: "Castellazzo",
    luogo: "Castellazzo (PR)",
    anno: 2022,
    categoria: "coperture-tamponamenti",
    settore: "Terziario",
    inEvidenza: false,
    descrizione:
      "Copertura di un maneggio con capriate reticolari di grande luce e tamponamenti laterali parziali per la ventilazione naturale. Progetto ottimizzato sul peso proprio per contenere le fondazioni.",
    datiTecnici: [
      { label: "Luce capriate", valore: "28,00 m" },
      { label: "Superficie coperta", valore: "1.320 m²" },
      { label: "Tonnellaggio acciaio", valore: "~ 65 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [
      "Capriate reticolari in tubolari S355",
      "Arcarecci in profili sagomati a freddo",
      "Copertura in pannelli grecati coibentati",
    ],
  },
  {
    id: "8",
    slug: "pensilina-auto-fotovoltaica-pinko",
    titolo: "Pensilina auto fotovoltaica",
    cliente: "Pinko",
    luogo: "Fidenza (PR)",
    anno: 2024,
    categoria: "pensiline-auto",
    settore: "Commerciale",
    inEvidenza: false,
    descrizione:
      "Pensilina per l'area di sosta aziendale predisposta per l'impianto fotovoltaico in copertura. Moduli ripetibili su file di stalli, con canalizzazioni integrate nei montanti per i cavi.",
    datiTecnici: [
      { label: "Posti auto coperti", valore: "24" },
      { label: "Superficie", valore: "600 m²" },
      { label: "Predisposizione FV", valore: "Sì" },
      { label: "Tonnellaggio acciaio", valore: "~ 28 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [
      "Montanti e travi in profili HEA S275",
      "Copertura in lamiera grecata predisposta FV",
      "Bulloneria classe 8.8 zincata",
    ],
  },
  {
    id: "9",
    slug: "soppalco-stoccaggio-parmalat",
    titolo: "Soppalco di stoccaggio",
    cliente: "Parmalat",
    luogo: "Collecchio (PR)",
    anno: 2021,
    categoria: "strutture-acciaio",
    settore: "Industriale",
    inEvidenza: false,
    descrizione:
      "Soppalco industriale di stoccaggio ricavato all'interno di un capannone esistente, dimensionato per i sovraccarichi di magazzino. Piano in lamiera grecata e getto collaborante, con scala e cancello di carico a norma.",
    datiTecnici: [
      { label: "Superficie soppalco", valore: "540 m²" },
      { label: "Sovraccarico di progetto", valore: "600 kg/m²" },
      { label: "Tonnellaggio acciaio", valore: "~ 38 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [...MATERIALI_STD, "Lamiera grecata collaborante"],
  },
  {
    id: "10",
    slug: "frangisole-lamiera-iris",
    titolo: "Frangisole in lamiera",
    cliente: "Iris",
    luogo: "Reggio Emilia",
    anno: 2020,
    categoria: "rivestimenti-facciata",
    settore: "Industriale",
    inEvidenza: false,
    descrizione:
      "Sistema frangisole verticale in lame di lamiera piegata su facciata sud, per il controllo dell'irraggiamento. Passo delle lame calibrato con lo studio solare del cliente.",
    datiTecnici: [
      { label: "Superficie schermata", valore: "480 m²" },
      { label: "Passo lame", valore: "250 mm" },
      { label: "Tonnellaggio acciaio", valore: "~ 11 t" },
    ],
    lavorazioni: [
      "Taglio laser e piegatura delle lame",
      "Zincatura a caldo e verniciatura RAL",
      "Assemblaggio dei moduli in officina",
      "Montaggio su sottostruttura a parete",
    ],
    materiali: [
      "Lame in lamiera d'acciaio piegata",
      "Sottostruttura in tubolari S275",
      "Staffe di ancoraggio zincate",
    ],
  },
  {
    id: "11",
    slug: "pensilina-carico-bervini",
    titolo: "Pensilina di carico",
    cliente: "Bervini",
    luogo: "Noceto (PR)",
    anno: 2023,
    categoria: "pensiline",
    settore: "Commerciale",
    inEvidenza: false,
    descrizione:
      "Pensilina sulle baie di carico per proteggere merce e operatori durante le operazioni logistiche. Sbalzo calibrato sull'ingombro dei mezzi e integrazione con i respingenti esistenti.",
    datiTecnici: [
      { label: "Sviluppo", valore: "36,00 m" },
      { label: "Sbalzo", valore: "4,50 m" },
      { label: "Tonnellaggio acciaio", valore: "~ 22 t" },
    ],
    lavorazioni: LAVORAZIONI_STD,
    materiali: [...MATERIALI_STD, "Copertura in lamiera grecata"],
  },
  {
    id: "12",
    slug: "struttura-residenziale-ricci",
    titolo: "Struttura residenziale su misura",
    cliente: "Ricci",
    luogo: "Noceto (PR)",
    anno: 2022,
    categoria: "opere-speciali",
    settore: "Terziario",
    inEvidenza: false,
    descrizione:
      "Carpenteria di dettaglio per un intervento residenziale: telaio d'acciaio per l'ampliamento a sbalzo, travi a vista e elementi su disegno d'architetto. Lavorazione di precisione e finiture curate.",
    datiTecnici: [
      { label: "Superficie", valore: "140 m²" },
      { label: "Aggetto a sbalzo", valore: "3,20 m" },
      { label: "Tonnellaggio acciaio", valore: "~ 9 t" },
    ],
    lavorazioni: [
      "Taglio e piegatura di precisione",
      "Saldatura a vista molata",
      "Zincatura e verniciatura a campione",
      "Montaggio con posa assistita",
    ],
    materiali: MATERIALI_STD,
  },
];

export const mockProgetti: Progetto[] = real.map((p) => ({
  id: p.id,
  slug: p.slug,
  titolo: p.titolo,
  cliente: p.cliente,
  luogo: p.luogo,
  anno: p.anno,
  categoria: { slug: p.categoria, nome: CATEGORIA_NOME[p.categoria] },
  settore: p.settore,
  inEvidenza: p.inEvidenza,
  descrizione: p.descrizione,
  galleria: galleria(p.titolo),
  datiTecnici: p.datiTecnici,
  lavorazioni: p.lavorazioni,
  materiali: p.materiali,
  seo: {
    title: `${p.titolo} — ${p.cliente}`,
    description: `${p.titolo} in carpenteria metallica per ${p.cliente} a ${p.luogo} (${p.anno}). ${CATEGORIA_NOME[p.categoria]} su commessa.`,
  },
}));
