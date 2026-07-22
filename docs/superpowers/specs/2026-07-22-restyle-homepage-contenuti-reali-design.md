# Design — Restyle homepage su struttura sito storico + contenuti reali

Data: 2026-07-22

## Contesto e obiettivo

Il sito attuale (Next.js static export + WordPress headless, linguaggio "blueprint / ingegneria editoriale" da `CLAUDE.md`) ha già l'architettura informativa corretta per un sito vetrina di realizzazioni, ma è interamente popolato con segnaposto: icone SVG astratte al posto di foto, box "Foto" grigi, 12 progetti fittizi con immagini placeholder generate via GD.

Il cliente ha chiesto di riportare la homepage alla **stessa sequenza di blocchi** del sito storico Edilmetal (rilevato da web.archive.org, snapshot 2024-09-01: hero fotografico → "In evidenza" 2 categorie → "Realizzazioni" banda scura con griglia foto → banda Contatti/Orari/Telefono → footer), reinterpretata nel linguaggio visivo blueprint attuale (non uno stile retrò), e di popolare tutto con **materiali reali**: le ~184 foto di cantiere organizzate per categoria trovate in `sito web edilmetal 2018/`.

Le sezioni della home attuale non presenti nel sito storico (statistiche aziendali, "come lavoriamo", "perché sceglierci", loghi clienti, CTA finale) vengono rimosse dalla home — non da tutto il sito: `servizi` e `azienda` hanno già propri campi WP equivalenti (`processo`, `vantaggi`/`valori`, `stats`) indipendenti da quelli della home, quindi non perdono contenuto.

## Scoperta rilevante

Verificando il contratto reale frontend↔WordPress si è trovato che:
- Il campo immagine hero (`edilmetal_home_hero_img`) esiste già in Meta Box ma il DTO PHP (`PagePresenter::home_hero()`) non lo espone, e il componente React (`Hero.tsx`) non lo usa comunque (mostra sempre un disegno SVG).
- I campi usati dalla home React per `processo`, `perche`, `referenze`, `cta` **non esistono nel presenter PHP** (`PagePresenter::to_dto()` per `home` mappa solo `hero`, `stats`, `intro`): sono usati solo dal mock di fallback. Rimuoverli dalla home non rompe nulla di realmente funzionante, anzi elimina un disallineamento API preesistente.

## Architettura — nessun cambio strutturale

Resta invariato: WP headless (CPT `progetto`, tassonomia `categoria_opera`/`settore`), REST `edilmetal/v1/*`, Next.js static export, componenti Server Component di default. Nessuna nuova pagina, nessun nuovo CPT.

## 1. Nuova sequenza blocchi Home

`Hero (foto reale) → In evidenza (2 categorie) → Realizzazioni (banda scura, foto reali) → Contatti/Orari/Telefono (globale) → Footer`

### Hero
- Sostituito lo sfondo/disegno `Blueprint` SVG con una foto reale (`parmalat 1.jpg`, gru che monta un telaio in acciaio — stessa inquadratura dell'hero del sito storico) mostrata full-bleed dietro il blocco testo.
- Testo, CTA, barra indice mono restano come oggi (stile blueprint).
- `home_hero()` nel presenter PHP viene esteso per esporre `image` (usa il campo Meta Box già esistente `edilmetal_home_hero_img`).

### In evidenza (nuovo blocco)
- Esattamente 2 card: foto copertina (reale) + nome categoria + CTA "Vai alla sezione prodotti" → link a `/servizi` (o `/realizzazioni?categoria=slug`).
- Editabile in WP: nuovo gruppo ripetuto in `PageFields::home_box()` con 2 voci, ciascuna `{ categoria (select da tassonomia categoria_opera), immagine, cta_url opzionale }`.
- Sostituisce sulla home l'indice numerato a 8 categorie (`CategoryIndex`), che resta invariato su `/servizi` dove è già usato.

### Realizzazioni (nuovo blocco, banda scura)
- Sezione full-bleed sfondo `--deep`, titolo "Realizzazioni" in accento arancio, griglia/rail di foto reali dai progetti con `inEvidenza = true` (riusa il meccanismo esistente `getProgetti({ filters: { inEvidenza: true } })`, nessun nuovo campo WP).
- Nessun nuovo componente dati: solo un nuovo componente di presentazione (variante scura del rail/card esistente).

### Contatti / Orari / Telefono (nuovo componente globale)
- 3 colonne con icona: Contatti (indirizzo, email), Orari ufficio (da `settings.orari`, già esistente), Telefono e Fax.
- Renderizzato nel layout locale (`[locale]/layout.tsx`) tra il contenuto di pagina e il `Footer`, quindi visibile su **tutte** le pagine (home, servizi, azienda, realizzazioni, contatti) — comportamento identico al sito storico, dove questa banda compariva ovunque.
- Richiede un nuovo campo opzionale `fax` in `SiteSettings` (dominio + `SettingsFields.php` + `SettingsPresenter.php`), popolato con "0521 615207" (valore reale dal sito storico).

### Sezioni rimosse dalla Home
`StatsRow`, `Flow` (processo), `Feats` (perché), `Clients` (referenze), `CtaBand` finale spariscono da `page.tsx` della home. I componenti condivisi (`Flow`, `Feats`, `StatsRow`, `CtaBand`) **non vengono eliminati**: restano in uso su `/servizi` e `/azienda`. Il mock `pages.ts` e i campi Meta Box `edilmetal_home_stats` / `edilmetal_home_intro_*` (mai realmente esposti dal presenter, vedi sopra) vengono rimossi per pulizia.

## 2. Contenuti reali — pipeline di seeding

Le foto in `sito web edilmetal 2018/<N categoria>/` vengono raggruppate per nome-base file (rimuovendo il suffisso numerico finale), producendo ~84 progetti su 184 foto totali, con la categoria dedotta dalla cartella di origine (mappatura 1:1 con gli 8 slug di `categoria_opera`).

Esempio: `aiassa 1.JPG`, `aiassa 2.jpg`, `aiassa 3.JPG` in `2 strutture miste/` → un progetto `titolo: "Aiassa"`, `categoria: strutture-miste`, galleria di 3 immagini.

Una decina di gruppi ambigui vengono risolti manualmente durante l'implementazione (es. `ricci casa-apr07 002-1.jpg` + `ricci casa-apr07 003/007.jpg` uniti in un solo progetto; file senza nome cliente riconoscibile come `P17-10-08_11.10.jpg` scartati o rinominati con un titolo generico di categoria).

Campi obbligatori a schema (`luogo`, `anno`, `descrizione`) non possono restare vuoti: vengono valorizzati con segnaposto onesti e riconoscibili come tali (`luogo: "Provincia di Parma"`, `anno: 2018`, `descrizione`: una riga generica per categoria), pensati per essere sovrascritti dall'editor in WP admin. `datiTecnici`, `lavorazioni`, `materiali` restano array vuoti.

Meccanismo: si estende `MediaSeeder` esistente (idempotente, già usato per i placeholder) per importare i file reali copiati in `cms/seed/media/realizzazioni/<categoria-slug>/<progetto-slug>/NN.jpg`, e si sostituisce/estende `Catalog::progetti()` con la lista completa generata dal raggruppamento (mantenendo gli 12 progetti già presenti dove il nome cliente coincide, per non perdere le descrizioni già scritte a mano).

## 3. Verifica locale

1. `npm run dev:up` + `npm run dev:setup` (Docker: WordPress + MariaDB + phpMyAdmin).
2. Seed via `npm run dev:seed` (o wp-cli diretto) con i nuovi dati.
3. `cd web && npm run dev`, `npm run typecheck`, `npm run lint`, `npm run test`.
4. Confronto visivo pagina per pagina (home, servizi, realizzazioni, scheda progetto, azienda, contatti) con screenshot, verifica accessibilità (axe) e Core Web Vitals di base.
5. Presentazione al cliente in locale prima di considerare il deploy sul dominio reale.

## Fuori scope

- Nessuna sezione "News" (il vecchio sito la aveva ma non esiste un CPT news nel dominio dati attuale — non richiesto esplicitamente, non viene aggiunto).
- Nessun cambio alla lingua/i18n oltre alle nuove chiavi di traduzione necessarie ai nuovi blocchi.
- Nessun cambio al flusso di deploy (GitHub Actions → Plesk) in questo giro: resta locale finché non approvato.
