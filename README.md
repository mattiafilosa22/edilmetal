# Edilmetal S.r.l. — Sito web

Sito ufficiale di **Edilmetal S.r.l.** (Noceto, PR): carpenteria metallica **su commessa** per l'edilizia industriale, commerciale e terziaria. Vetrina editoriale di **realizzazioni** (case study di strutture in acciaio) + richiesta preventivo.

Architettura **headless**: WordPress = solo back-office/API (PHP), frontend **Next.js in static export** servito da hosting statico (Plesk). Vedi [`CLAUDE.md`](./CLAUDE.md) per le regole di sviluppo e [`docs/api-contract.md`](./docs/api-contract.md) per il contratto REST.

## Requisiti
- Node.js ≥ 20
- Docker (Desktop) in esecuzione
- Composer + PHP 8.x (solo per gli standard PHP in `cms/`)

## Setup rapido — ambiente locale Docker (DB containerizzato)
Stack `docker-compose`: **WordPress + MariaDB (container) + phpMyAdmin + wp-cli**. Porte dedicate (8890/8891/3307) per non collidere con altri servizi.

```bash
# 1) Avvia lo stack (WordPress headless + DB + phpMyAdmin)
npm install
npm run dev:up            # docker compose up -d
npm run dev:setup         # installa WP core + plugin free (Meta Box/Polylang/Fluent Forms/Complianz) + permalink + seeder

# 2) Frontend Next.js (punta all'API del WordPress locale)
cd web && npm install
# in web/.env.local:  WP_API_URL=http://localhost:8890/wp-json/edilmetal/v1
npm run dev              # http://localhost:3000
```

Indirizzi dell'ambiente:
| Servizio | URL | Credenziali |
|---|---|---|
| WordPress admin | http://localhost:8890/wp-admin | `admin` / `admin` |
| API headless | http://localhost:8890/wp-json/edilmetal/v1/settings | — |
| phpMyAdmin (DB) | http://localhost:8891 | `edilmetal` / `edilmetal` |
| Frontend Next.js | http://localhost:3000 | — |

> Il frontend ha un **mock** di fallback: `npm run build`/`dev` funziona anche senza lo stack Docker attivo (usato per la CI di export).

> **Servire l'export in locale**: usa `npx serve out` (rispetta gli `index.html` di directory). NON usare `serve -s out`: il flag `-s` (SPA) riscrive ogni rotta sull'`index.html` root e, con `trailingSlash:true`, causa un loop di redirect.

## Comandi principali
| Comando | Descrizione |
|---|---|
| `npm run dev:up` / `dev:down` | Avvia / ferma lo stack Docker |
| `npm run dev:destroy` | Ferma e **cancella i volumi** (DB pulito) |
| `npm run dev:setup` | Installa WP + plugin + seeder |
| `npm run dev:seed` | Riesegue solo il seeder (`wp edilmetal seed`) |
| `npm run dev:cli -- <args>` | wp-cli nel container (es. `npm run dev:cli -- plugin list`) |
| `npm run dev:logs` | Log del container WordPress |
| `npm run dev` | Dev server Next.js |
| `npm run build` | Build + static export in `web/out/` |
| `npm run lint` / `npm run typecheck` / `npm run test` | Qualità frontend |

> Alternativa rapida senza phpMyAdmin: `npm run wp:start` (wp-env, porta 8888). Usa **uno** dei due ambienti, non entrambi insieme.

## Struttura
- `web/` — frontend Next.js (App Router, TypeScript, static export).
- `cms/` — codice WordPress custom (mu-plugin `edilmetal-core`, seeder, config). Il core WP **non** è versionato.
- `wireframes/` — riferimento di design (statico, non parte del sito pubblicato).
- `.github/workflows/` — pipeline di deploy (build export → SFTP/rsync su Plesk).
- `docs/` — contratto API, setup deploy, note.

## Deploy
GitHub Actions builda l'export statico e lo pubblica su **Plesk** via SFTP/rsync; un webhook WordPress (su publish) può innescare il rebuild automatico. Credenziali/segreti solo in GitHub Secrets o in file locali non tracciati (`scripts/.plesk.env`) — mai nel repo. Vedi [`docs/deploy-setup.md`](./docs/deploy-setup.md).
