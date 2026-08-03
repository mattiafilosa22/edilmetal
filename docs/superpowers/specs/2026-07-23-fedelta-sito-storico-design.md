# Design — Fedeltà alla struttura del sito storico (hero + pagine interne)

Data: 2026-07-23

## Contesto

Il restyle precedente (spec `2026-07-22-restyle-homepage-contenuti-reali-design.md`) ha riportato la home alla sequenza di blocchi del sito storico, ma ha mantenuto nell'hero elementi (titolo, sottotitolo, 2 CTA, barra indice) che il sito storico non aveva — l'hero storico era una foto a piena larghezza senza alcun testo sopra. Verificando le altre pagine editoriali (Prodotti, Chi siamo, Contattaci) sul sito archiviato, emerge lo stesso pattern: le pagine attuali hanno accumulato sezioni editoriali (statistiche, processo, valori, callout, banda CTA, rail "officina", mappa) che il sito storico non aveva affatto. Questo design le riporta a un contenuto strettamente fedele al sito storico, pagina per pagina.

## 1. Home — Hero

**Prima**: griglia a 2 colonne, testo (eyebrow/h1/sottotitolo/2 CTA) a sinistra, foto/disegno blueprint a destra, barra indice mono sotto.

**Dopo**: foto a piena larghezza, nessun testo/CTA/barra indice sopra — esattamente come il sito storico (solo la foto della gru che monta la struttura, poi sotto "In evidenza").

Il componente `Hero` resta in uso altrove (nessuna altra pagina lo consuma oggi, ma l'interfaccia va preservata per compatibilità futura); si aggiunge una modalità "solo foto" usata dalla home.

## 2. Servizi (= "Prodotti" del sito storico)

Il vecchio "Prodotti" era: hero con titolo in overlay + un semplice elenco delle 8 categorie (link testuali), nient'altro.

**Tolgo**: sezione "Come lavoriamo" (processo/flow), sezione "Perché sceglierci" (vantaggi) + callout relazioni di calcolo, banda CTA finale.
**Resto**: hero + elenco categorie (`CategoryIndex`, già esistente — stesso contenuto del vecchio elenco, solo con numerazione e frecce hover, non è una sezione nuova ma la stessa lista modernizzata nello stile).

## 3. Azienda (= "Chi siamo" del sito storico)

Il vecchio "Chi siamo" era: hero con titolo in overlay + heading "LA SOCIETÀ" + 3 paragrafi di storia, nient'altro (nessuna mappa, nessuna statistica, nessun team, nessuna rail fotografica).

**Tolgo**: statistiche (1997/500+/8/4,5★), valori (4 card), rail "Dentro l'officina", sezione "La sede" con mappa Leaflet, banda CTA finale.
**Resto**: hero + storia testuale.

## 4. Contatti

Il vecchio "Contattaci" era: hero + form di contatto + nota privacy, poi la banda globale Contatti/Orari/Telefono (già presente su ogni pagina) — **nessuna mappa**.

**Tolgo**: la mappa Leaflet (`SiteMap`) dalla pagina contatti.
**Resto**: form di richiesta + nota privacy.

## 5. Realizzazioni

Il vecchio sito non aveva un portfolio unico filtrabile: ogni categoria aveva una propria pagina con una griglia fotografica di quella sola categoria. Il modello dati attuale (CPT unico `progetto`, 94 realizzazioni) rende necessaria un'unica pagina, ma i controlli "moderni" (settore, anno, ordinamento, paginazione) non hanno corrispondenza nel sito storico.

**Tolgo**: filtro per settore, filtro per anno, dropdown di ordinamento, paginazione.
**Resto**: filtro a chip per categoria (fedele all'IA "una vista per categoria" del vecchio sito) + griglia con tutti i risultati della categoria selezionata (o tutte le 94 se nessuna categoria è selezionata), senza pagine.

## Fuori scope

- Nessun cambio al modello dati WordPress (CPT/tassonomie/meta) — sono modifiche solo di presentazione frontend.
- Nessun cambio alla banda globale Contatti/Orari/Telefono né al footer.
- Nessun cambio a `/realizzazioni/[slug]` (scheda progetto).
