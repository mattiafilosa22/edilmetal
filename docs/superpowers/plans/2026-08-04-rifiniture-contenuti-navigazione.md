# Rifiniture contenuti e navigazione Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rimuovere i contenuti indicati, rendere verificabile la navigazione delle card e aggiornare la modale di avviso senza alterare CMS o API.

**Architecture:** Interventi esclusivamente nel livello di presentazione Next.js. Le pagine smettono di renderizzare i due blocchi ridondanti, `ProjectCard` resta il confine unico della navigazione al dettaglio e `SiteNotice` conserva il comportamento esistente con markup e messaggi semplificati.

**Tech Stack:** Next.js 16 App Router, React 19, TypeScript strict, next-intl, Vitest, Testing Library, ESLint.

## Global Constraints

- Non modificare CMS, DTO, URL, comportamento di chiusura della modale o persistenza in `sessionStorage`.
- La rimozione dei contenuti deve avvenire dal markup, non tramite CSS.
- Il titolo italiano della modale deve essere esattamente “Stiamo lavorando per rendere il sito ancora più fruibile.” e il testo “Sito in aggiornamento.”.
- La modale non deve renderizzare né “00” né “Avviso”; deve mantenere `aria-labelledby` e i controlli di chiusura accessibili.
- Ogni card deve essere un unico link verso `/{locale}/realizzazioni/{slug}` e il drag della rail non deve generare navigazioni accidentali.
- TypeScript strict, nessun `any`, contenuti editoriali non duplicati nel codice di componente.

---

### Task 1: Rifiniture UI, navigazione e avviso

**Files:**
- Create: `web/src/app/[locale]/azienda/page.test.tsx`
- Create: `web/src/app/[locale]/realizzazioni/[slug]/page.test.tsx`
- Modify: `web/src/app/[locale]/azienda/page.tsx`
- Modify: `web/src/app/[locale]/realizzazioni/[slug]/page.tsx`
- Modify: `web/src/components/ui/ProjectCard.test.tsx`
- Modify: `web/src/components/ui/SiteNotice.test.tsx`
- Modify: `web/src/components/ui/SiteNotice.tsx`
- Modify: `web/src/i18n/messages/it.json`
- Modify: `web/src/i18n/messages/en.json`

**Interfaces:**
- Consumes: `PageContent.azienda.storia`, `Progetto.descrizione`, `ProgettoSummary.slug`, namespace next-intl `SiteNotice`.
- Produces: markup pagina Azienda senza `storiaTitolo`; testata scheda senza descrizione; card-link `/${locale}/realizzazioni/${slug}`; modale senza kicker.

- [ ] **Step 1: Scrivere i test RED delle pagine**

Creare test di rendering delle pagine con dipendenze server/API sostituite da fixture complete. Per Azienda asserire che i paragrafi della storia siano visibili e “La società” non sia nel documento. Per la scheda asserire che la descrizione non compaia nella `page-head` ma resti nel tab descrizione. Il cambiamento di produzione che deve far fallire questi test è la reintroduzione dei due blocchi rimossi.

- [ ] **Step 2: Rafforzare i test RED di card e modale**

In `ProjectCard.test.tsx` verificare che l’unico link contenga immagine, heading e luogo e abbia `href="/it/realizzazioni/bervini"`. In `SiteNotice.test.tsx` usare i nuovi messaggi e asserire titolo, testo, assenza di “00” e assenza di “Avviso”.

- [ ] **Step 3: Eseguire i test mirati e osservare il fallimento previsto**

Run: `cd web && npm test -- src/app/[locale]/azienda/page.test.tsx src/app/[locale]/realizzazioni/[slug]/page.test.tsx src/components/ui/ProjectCard.test.tsx src/components/ui/SiteNotice.test.tsx`

Expected: FAIL perché le pagine renderizzano ancora i blocchi e la modale renderizza ancora il kicker/copy precedente; i test preesistenti della card possono già passare e fungono da caratterizzazione.

- [ ] **Step 4: Applicare l’implementazione minima**

In `azienda/page.tsx` rimuovere soltanto il `Reveal` contenente `<h2>{azienda.storiaTitolo}</h2>`. In `realizzazioni/[slug]/page.tsx` rimuovere soltanto il `div` della descrizione dentro `.sp-head`. In `SiteNotice.tsx` rimuovere l’intero `.notice__kick` e mantenere titolo, testo e pulsanti. Aggiornare `it.json` col copy esatto e `en.json` con “We are working to make the site even easier to use.” / “Site under update.”; rimuovere la chiave `kicker` da entrambe le lingue se non più consumata.

- [ ] **Step 5: Eseguire i test mirati fino al GREEN**

Run: `cd web && npm test -- src/app/[locale]/azienda/page.test.tsx src/app/[locale]/realizzazioni/[slug]/page.test.tsx src/components/ui/ProjectCard.test.tsx src/components/ui/SiteNotice.test.tsx`

Expected: PASS, nessun warning o errore.

- [ ] **Step 6: Eseguire la suite completa di qualità**

Run: `cd web && npm test && npm run typecheck && npm run lint && npm run build`

Expected: tutti i comandi terminano con exit code 0.

- [ ] **Step 7: Commit dell’implementazione**

Run: `git add web/src/app/'[locale]'/azienda/page.tsx web/src/app/'[locale]'/azienda/page.test.tsx web/src/app/'[locale]'/realizzazioni/'[slug]'/page.tsx web/src/app/'[locale]'/realizzazioni/'[slug]'/page.test.tsx web/src/components/ui/ProjectCard.test.tsx web/src/components/ui/SiteNotice.tsx web/src/components/ui/SiteNotice.test.tsx web/src/i18n/messages/it.json web/src/i18n/messages/en.json && git commit -m "fix(web): refine project content and site notice"`

Expected: commit creato senza includere file estranei già presenti nel worktree.

---

### Task 2: Deploy Plesk verificato

**Files:**
- No code changes.

**Interfaces:**
- Consumes: branch Git corrente, remote `origin`, workflow `.github/workflows/deploy-plesk.yml`.
- Produces: commit remoto e run GitHub Actions conclusa con successo.

- [ ] **Step 1: Pubblicare il branch**

Run: `git push -u origin fix/popup-hero-slider-privacy`

Expected: push riuscito e branch remoto aggiornato al commit verificato.

- [ ] **Step 2: Avviare il workflow di deploy sul ref pubblicato**

Run: `gh workflow run deploy-plesk.yml --ref fix/popup-hero-slider-privacy`

Expected: GitHub accetta un nuovo run `workflow_dispatch`.

- [ ] **Step 3: Verificare il deploy**

Run: `gh run list --workflow deploy-plesk.yml --branch fix/popup-hero-slider-privacy --limit 1` e poi `gh run watch <run-id> --exit-status`.

Expected: run conclusa con stato `success`; build con dati WordPress reali e mirror FTPS su Plesk completati.
