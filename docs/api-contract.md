# Contratto API `wp-json/edilmetal/v1/*`

Il contratto **canonico** è definito dagli schemi zod del frontend in
`web/src/domain/*.ts`. Il layer PHP (Repository → Presenter → Controller) deve
emettere **esattamente** quella forma. Gli schemi zod validano ogni risposta al
confine (`web/src/lib/api/client.ts`): un DTO non conforme viene rifiutato.

Fonte di verità degli schemi:

- Progetto (realizzazione) → `web/src/domain/progetto.ts`
- Pagine → `web/src/domain/page.ts`
- Impostazioni → `web/src/domain/settings.ts`
- Lead → `web/src/domain/lead.ts`

I Presenter PHP che producono questi DTO:

- `cms/mu-plugins/edilmetal-core/rest/Presenters/ProgettoPresenter.php`
- `cms/mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php`
- `cms/mu-plugins/edilmetal-core/rest/Presenters/SettingsPresenter.php`

> Nota: zod di default **scarta le chiavi sconosciute**. Il Presenter può
> includere campi extra (ignorati), ma i campi richiesti devono essere presenti
> e del tipo corretto.

---

## GET `/progetti` → `ProgettoSummary[]`

Parametri (tutti opzionali): `lang` (`it`), `categoria` (slug tax `categoria_opera`),
`settore` (slug tax `settore`), `anno` (int), `inEvidenza` (`1` per il rail home).

Ogni elemento:

| Campo | Tipo | Origine CMS / normalizzazione |
|---|---|---|
| `id` | string | `post->ID` come stringa |
| `slug` | string | `post_name` |
| `titolo` | string | `post_title` |
| `cliente` | string | meta `cliente` |
| `luogo` | string | meta `luogo` (es. "Collecchio (PR)") |
| `anno` | int | meta `anno` |
| `categoria` | `{ slug, nome }` | termine primario tax `categoria_opera` |
| `settore` | string (opzionale) | nome termine primario tax `settore`, se presente |
| `inEvidenza` | bool | meta `in_evidenza` |
| `copertina` | `Image` | prima immagine della galleria (fallback: featured image) |

**8 categorie** (`categoria_opera`): `strutture-acciaio`, `strutture-miste`,
`scale`, `pensiline`, `pensiline-auto`, `coperture-tamponamenti`,
`rivestimenti-facciata`, `opere-speciali`.

**`Image`**: `{ src, srcset?, width>0, height>0, alt }`. `src` è la size `large`
(fallback `medium`/`full`/`thumbnail`); `srcset` costruito da tutte le size
disponibili (`url width w`).

## GET `/progetti/{slug}` → `Progetto`

Tutti i campi di `ProgettoSummary` (senza `copertina`) più:

| Campo | Tipo | Origine |
|---|---|---|
| `descrizione` | string (HTML sanificato) | `post_content` / meta `descrizione` |
| `galleria` | `Image[]` (min 1) | meta `galleria` |
| `datiTecnici` | `{ label, valore }[]` | scheda tecnica: superficie (m²), peso acciaio (t), luce/campata (m), altezza (m), tipologia, ecc. (solo voci valorizzate) |
| `lavorazioni` | string[] | meta `lavorazioni` (una voce per riga → array) |
| `materiali` | string[] | meta `materiali` (una voce per riga → array) |
| `seo` | `{ title?, description?, ogImage? }` (opzionale) | campi SEO editoriali; fallback derivati da titolo/descrizione/copertina |

I **progetti correlati** non sono nel DTO: il frontend li deriva a build time
(stessa `categoria`, escluso il corrente, primi N).

## GET `/pages/{key}` → `PageContent`

Parametri: `lang`. `key` ∈ `home|servizi|azienda|contatti`.

Sempre: `key`, `title`, `seo?`. Blocchi tipizzati opzionali (inclusi solo se
valorizzati):
- `home`: `hero` (blocco storico), `inEvidenza` (max 2 categorie con foto reale).
- `servizi`: `tipologie` (lista famiglie di opere, con dettaglio).
- `azienda`: `storiaTitolo`, `storia` (paragrafi).
- `contatti`: `intro`, riferimenti (da `/settings`).

`home.hero` (blocco storico, `HomeHero`):

| Campo | Tipo | Note |
|---|---|---|
| `eyebrow` | string (opzionale) | occhiello sopra il titolo |
| `title` | string | titolo principale |
| `titleAccent` | string (opzionale) | porzione del titolo con enfasi grafica |
| `subtitle` | string | sottotitolo |
| `ctaPrimary` | `{ label, href }` | CTA primaria (fallback: "Le realizzazioni" → `/realizzazioni`) |
| `ctaSecondary` | `{ label, href }` (opzionale) | CTA secondaria |
| `index` | `{ valore, etichetta }[]` (max 3) | barra-indice mono sotto l'hero |

`home.inEvidenza` (`HomeFeatured[]`, max 2, blocco storico): per ogni voce
`categoria` (`{ slug, nome }`, termine reale di `categoria_opera`) e `immagine`
(`Image`, foto reale caricata in WP). Assente/omesso se lo slot non è
valorizzato (categoria o immagine mancante).

`servizi.tipologie` (`ServiziContent`, `CategoriaRef[]`): una voce per
famiglia di `categoria_opera` valorizzata, con `slug` (`CategoriaSlug`),
`nome` (termine reale) e `dettaglio` (descrizione del termine tassonomia,
fallback al `nome` se la descrizione non è compilata).

`azienda` (`AziendaContent`): `storiaTitolo` (string) e `storia` (`string[]`,
un paragrafo per elemento, derivato spezzando il campo WYSIWYG editoriale
sui tag `<p>`).

## GET `/settings` → `SiteSettings`

`nomeAzienda`, `ragioneSociale`, `partitaIva`, `indirizzo`, `telefono`, `email`
(stringhe non vuote), `fax` (opzionale, dato storico), `coordinate`
(`{lat, lng}` per Leaflet), `mapsUrl` (opzionale; assente ⇒ fallback
OpenStreetMap), `orari` (`{giorni, apertura}[]`, parsati da `Giorni: Apertura`
una fascia per riga), `social` (`facebook`/`instagram`/`linkedin`, inclusi solo
se valorizzati), `heroImage` (`Image`, opzionale, foto hero della home
editabile globalmente in WP → Impostazioni), `fotoCredit` (string, opzionale,
credito fotografico mostrato accanto all'hero/footer).

> Nessun prezzo/carrello: dominio B2B su commessa, non retail.

---

## Modalità STRICT del data layer

`web/src/lib/api` di default prova la REST WordPress e, in caso di errore di
fetch o di validazione zod, **ricade sul dataset mock** (`mock/*`), così
`npm run build` funziona anche senza CMS.

Impostando **`WP_API_STRICT=1`** il fallback è disattivato: ogni errore viene
propagato, facendo fallire il build statico. Serve a verificare che il frontend
live consumi i dati reali senza mascherare disallineamenti col CMS.

```bash
cd web
WP_API_STRICT=1 WP_API_URL=http://localhost:8890/wp-json/edilmetal/v1 npm run build
```

Default (variabile assente) = fallback al mock.
Implementazione: `isStrict()` in `web/src/lib/api/client.ts`; `withFallback` /
`withNullableFallback` in `web/src/lib/api/index.ts`.
