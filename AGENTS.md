# Edilmetal S.r.l. — Sito web

Sito ufficiale di **Edilmetal S.r.l.** (Noceto, PR): carpenteria metallica **su commessa** per l'edilizia industriale, commerciale e terziaria. Progettazione, produzione e montaggio di strutture in acciaio, con relazioni di calcolo firmate da tecnici abilitati. Fondata nel 1997 (Alessio Ricci e Aldo Medioli). Non è un e-commerce: è una **vetrina editoriale di realizzazioni** (case study) + richiesta preventivo.

> **Dominio, contenuti e design** seguono il linguaggio "blueprint / ingegneria editoriale" (vedi `wireframes/`). Il modello dati è `progetto` (realizzazione): niente e-commerce, niente prezzi/carrello.

## Architettura
- **WordPress headless** (PHP 8.x, MySQL) su subdominio `cms.<dominio>` = SOLO back-office/API. Hosting **Plesk** (dominio già del cliente).
- **Frontend Next.js (App Router, TypeScript) in Static Export** (`output:'export'`) servito come file statici dal dominio principale.
- Dati via **REST custom** `wp-json/edilmetal/v1/*` (DTO formati da Presenter PHP). Nessun testo di contenuto hardcoded: tutto editabile in WP (testi, immagini, dati tecnici, SEO per pagina/progetto).
- Deploy: GitHub Actions builda l'export e lo pubblica su Plesk via SFTP/rsync; webhook WP su publish → `repository_dispatch` → rebuild.

## Stack
Next.js · React · TypeScript strict · next-intl · Leaflet/OSM (mappa cookieless) · zod · Vitest/Playwright/axe.
WordPress + **Meta Box** (free: campi/gallerie/SEO per contenuto) · **Polylang** (free: multilingua — **solo IT attiva ora, predisposizione per una 2ª lingua**) · **Fluent Forms** (free: form preventivo/lead) · **Complianz** (free: testi cookie/privacy) · ottimizzazione immagini (ShortPixel/Imagify free).
Regola versioni: ultima stabile; penultima se l'ultima ha bug bloccanti. Solo plugin gratuiti.

**SEO / indicizzazione AI**: gestite nel frontend statico (HTML pre-renderizzato): `metadata`/OG, JSON-LD schema.org (`Organization`, `LocalBusiness` Noceto, `CreativeWork`/`Project` per le realizzazioni), `sitemap.xml`, `robots.txt`, **`llms.txt`/`llms-full.txt`** per discovery LLM, `hreflang` predisposto. I meta SEO (title/description/OG) sono **editoriali per pagina e per progetto** (campi Meta Box).

## Dominio dati
- **CPT `progetto`** (slug pubblico `realizzazioni`): cliente, luogo, anno, categoria, settore, in-evidenza (rail home), descrizione, **dati tecnici** (superficie m², peso acciaio t, luce/campata m, altezza m, ecc.), lavorazioni, materiali, **galleria** (1–20 foto), meta SEO.
- **CPT `lead`**: richieste dal form preventivo.
- **Tassonomie**: `categoria_opera` (8 famiglie: strutture in acciaio, strutture miste, scale, pensiline, pensiline auto/carport, coperture e tamponamenti, rivestimenti di facciata, opere speciali), `settore` (industriale/commerciale/terziario). L'anno è un meta (filtro).
- **Contenuti editoriali** (Meta Box su pagine/opzioni): home (hero, stat, intro), servizi (processo, tipologie, callout), azienda (storia 1997, valori, team, sede+mappa, orari), contatti, settings globali (contatti, orari, social, footer, testi legali).
- **REST `edilmetal/v1`**: `GET /progetti` (filtri categoria/settore/anno/in-evidenza), `GET /progetti/{slug}`, `GET /pages/{key}`, `GET /settings`, `POST /lead`.

## Design
Linguaggio **"blueprint / ingegneria editoriale"** (vedi `wireframes/`): **light-first** con toggle chiaro/scuro, griglia blueprint, sezioni numerate con etichetta sticky, tipografia Space Grotesk (display) + Inter (testo) + IBM Plex Mono (dati), accento arancio tecnico. Palette: `--deep #0F2740`, `--anthra #1F2328`, `--steel #6B7785`, `--accent #D96B2B`, sfondo `#F4F7FA`. WCAG 2.1 AA, Core Web Vitals.

## Struttura
- `web/` frontend (`src/app/[locale]`, `components`, `features`, `lib`, `domain`, `i18n`, `styles`).
- `cms/mu-plugins/edilmetal-core/` codice WP (CPT, fields, rest, security, webhook, mail, seed); `cms/config/` wp-config/.htaccess.
- `wireframes/` riferimento di design (statico, non parte del sito). `docs/` spec, contratto API, deploy.
- `.Codex/agents/` implementer + reviewer.

## Regole di sviluppo
- SOLID, Clean Architecture, Clean Code. Niente spaghetti/duplicazione. Componenti piccoli, nomi espliciti.
- TS strict, no `any`, zod al confine dati. Server Components di default; `use client` solo se necessario. Compatibile export statico.
- PHP: WordPress Coding Standards + PSR-12, escaping/sanitizzazione sempre, REST con permission_callback, Repository/Presenter per i DTO.
- Accessibilità WCAG 2.1 AA e Core Web Vitals sono "definition of done" per ogni task UI.
- Contenuti dal WP (mai testo hardcoded); solo le label di UI stanno in `i18n`. Predisposizione multilingua attiva ma **una sola lingua (IT) pubblicata**.
- Sicurezza WP: auto-update disattivati; il ruolo cliente "Redattore Edilmetal" non può aggiornare/installare core o plugin.

## Comandi
- Ambiente locale Docker (WordPress + MariaDB + phpMyAdmin): `npm run dev:up` poi `npm run dev:setup`. WP admin http://localhost:8890/wp-admin, API http://localhost:8890/wp-json/edilmetal/v1, phpMyAdmin http://localhost:8891. wp-cli: `npm run dev:cli -- <args>`; seeder `npm run dev:seed`.
- Frontend: `cd web && npm run dev | build | lint | test`; `npm run typecheck`. Mock di fallback: builda anche senza WP.
- Qualità PHP: in `cms/` → `composer phpcs` / `composer phpcbf`.

## Workflow agenti
Ogni task: **implementer** implementa + si auto-valuta 1-10 e itera fino a 10/10; poi **reviewer** verifica in modo indipendente (build/test/lint/axe) e assegna 1-10; se <10 feedback → l'implementer ri-lavora. Task chiuso solo con reviewer 10/10 e verifiche verdi. Max 4 iterazioni poi escalation umana.
