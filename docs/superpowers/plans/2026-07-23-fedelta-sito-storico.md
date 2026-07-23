# Fedeltà al sito storico (hero + pagine interne) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Riportare l'hero della home e le pagine Servizi/Azienda/Contatti/Realizzazioni al contenuto esatto del sito storico (screenshot 2024 su web.archive.org), rimuovendo le sezioni editoriali accumulate che il sito storico non aveva.

**Architecture:** Solo modifiche di presentazione (componenti React + CSS + i18n). Nessun cambio al modello dati WordPress (CPT/tassonomie/meta/presenter PHP) — i campi editoriali non più mostrati restano nello schema/nel CMS, semplicemente non vengono più letti dalla UI.

**Tech Stack:** Next.js (App Router, TS strict, Server Components), Vitest + Testing Library, next-intl.

## Global Constraints

- TS strict, no `any`; nessun contenuto hardcoded oltre le label i18n.
- Nessun cambio a `web/src/domain/*` (i tipi restano compatibili, si smette solo di leggere alcuni campi).
- Nessun cambio a `cms/mu-plugins/edilmetal-core/**` (fuori scope, per spec approvata).
- Ogni task deve lasciare `cd web && npm run typecheck && npm run lint && npm run test` verde.
- Non rimuovere componenti condivisi (`Flow`/`Feats`/`StatsRow`/`Callout`/`SiteMap` in `web/src/components/ui/blocks.tsx` e `web/src/components/ui/SiteMap.tsx`) finché sono ancora usati da qualche pagina — solo l'ultimo task (Task 6) fa lo sweep finale quando nessuna pagina li usa più.

---

### Task 1: Hero — modalità "solo foto" per la home

**Files:**
- Modify: `web/src/components/ui/Hero.tsx`
- Modify: `web/src/styles/pages.css`
- Modify: `web/src/app/[locale]/page.tsx`
- Modify: `web/src/components/ui/Hero.test.tsx`

**Interfaces:**
- Produce: `Hero({ heroImage, fotoCredit, photoOnly? })` — nuova prop opzionale `photoOnly?: boolean` (default `false`, per compatibilità futura se il componente venisse riusato altrove con testo). Quando `photoOnly` è `true` e `heroImage` è presente, il componente renderizza SOLO la foto a piena larghezza (nessun eyebrow/h1/sottotitolo/CTA/barra indice). Se `photoOnly` è `true` ma `heroImage` è assente, mantiene il fallback attuale (disegno blueprint) per non lasciare la sezione vuota.
- Consuma: `HomeHero`, `Image` (esistenti, invariati).

- [ ] **Step 1: Aggiorna il test esistente per il nuovo caso "solo foto"**

Sostituisci il contenuto di `web/src/components/ui/Hero.test.tsx` con:

```tsx
import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Hero } from "./Hero";

const hero = {
  title: "Strutture in acciaio",
  subtitle: "Progettazione, produzione e montaggio su commessa.",
  ctaPrimary: { label: "Le realizzazioni", href: "/realizzazioni" },
  index: [],
};

describe("Hero", () => {
  it("renders the real photo when heroImage is provided", () => {
    render(
      <Hero
        hero={hero}
        locale="it"
        heroImage={{ src: "/mock/hero-parmalat.jpg", width: 1920, height: 1078, alt: "Gru in cantiere" }}
        fotoCredit="Archivio Edilmetal"
      />
    );
    expect(screen.getByAltText("Gru in cantiere")).toBeInTheDocument();
    expect(screen.getByText("Archivio Edilmetal")).toBeInTheDocument();
    expect(screen.queryByLabelText("Schema tecnico di un telaio in acciaio con capriata")).not.toBeInTheDocument();
  });

  it("falls back to the blueprint drawing when heroImage is absent", () => {
    render(<Hero hero={hero} locale="it" />);
    expect(screen.getByLabelText("Schema tecnico di un telaio in acciaio con capriata")).toBeInTheDocument();
  });

  it("in photoOnly mode with a photo, renders only the image — no title, subtitle, CTA or index bar", () => {
    render(
      <Hero
        hero={hero}
        locale="it"
        photoOnly
        heroImage={{ src: "/mock/hero-parmalat.jpg", width: 1920, height: 1078, alt: "Gru in cantiere" }}
      />
    );
    expect(screen.getByAltText("Gru in cantiere")).toBeInTheDocument();
    expect(screen.queryByRole("heading", { level: 1 })).not.toBeInTheDocument();
    expect(screen.queryByText(hero.subtitle)).not.toBeInTheDocument();
    expect(screen.queryByText(hero.ctaPrimary.label)).not.toBeInTheDocument();
  });

  it("in photoOnly mode without a photo, still falls back to the blueprint drawing (never blank)", () => {
    render(<Hero hero={hero} locale="it" photoOnly />);
    expect(screen.getByLabelText("Schema tecnico di un telaio in acciaio con capriata")).toBeInTheDocument();
  });
});
```

- [ ] **Step 2: Esegui i test per verificare il fallimento del nuovo caso**

Run: `cd web && npx vitest run src/components/ui/Hero.test.tsx`
Expected: FAIL sui 2 nuovi test (`photoOnly` non esiste ancora come prop, il componente ignora il valore e renderizza comunque titolo/CTA).

- [ ] **Step 3: Implementa**

Sostituisci il contenuto di `web/src/components/ui/Hero.tsx` con:

```tsx
import Image from "next/image";
import Link from "next/link";
import type { HomeHero, Image as ImageDto } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { Blueprint } from "./Blueprint";

type HeroProps = {
  hero: HomeHero;
  locale: Locale;
  /** Foto reale dell'hero (da `settings.heroImage`); assente ⇒ fallback al disegno blueprint. */
  heroImage?: ImageDto;
  /** Credito fotografico opzionale, mostrato sotto la foto (ignorato in modalità `photoOnly`). */
  fotoCredit?: string;
  /**
   * Modalità "solo foto" (sito storico): niente eyebrow/titolo/sottotitolo/
   * CTA/barra indice, solo la foto a piena larghezza. In assenza di foto
   * ricade comunque sul disegno blueprint, per non lasciare la sezione vuota.
   */
  photoOnly?: boolean;
};

function withLocale(locale: Locale, href: string): string {
  return `/${locale}${href.startsWith("/") ? href : `/${href}`}`;
}

/**
 * Hero della home. Due modalità:
 * - editoriale (default): testo + foto/disegno blueprint affiancati, con barra-indice mono;
 * - `photoOnly` (fedele al sito storico): solo foto a piena larghezza, nessun testo sopra.
 * Un solo `h1` per pagina (WCAG) — assente del tutto in modalità `photoOnly`.
 */
export function Hero({ hero, locale, heroImage, fotoCredit, photoOnly = false }: HeroProps) {
  if (photoOnly) {
    return (
      <section className="hero hero--photo-only">
        {heroImage ? (
          <div className="hero__full-photo">
            <Image
              src={heroImage.src}
              alt={heroImage.alt}
              width={heroImage.width}
              height={heroImage.height}
              sizes="100vw"
              priority
            />
          </div>
        ) : (
          <div className="container">
            <div className="hero__draw tick">
              <Blueprint
                ariaLabel="Schema tecnico di un telaio in acciaio con capriata"
                withDims
              />
            </div>
          </div>
        )}
      </section>
    );
  }

  return (
    <section className="hero">
      <div className="container">
        <div className="hero__grid">
          <div>
            {hero.eyebrow ? <span className="hero__eyebrow">{hero.eyebrow}</span> : null}
            <h1 className="hero__title">
              {hero.title}
              {hero.titleAccent ? (
                <>
                  {" "}
                  <em>{hero.titleAccent}</em>
                </>
              ) : null}
            </h1>
            <p className="hero__sub">{hero.subtitle}</p>
            <div className="hero__cta">
              <Link className="btn btn--deep btn--lg" href={withLocale(locale, hero.ctaPrimary.href)}>
                {hero.ctaPrimary.label}
              </Link>
              {hero.ctaSecondary ? (
                <Link
                  className="btn btn--outline btn--lg"
                  href={withLocale(locale, hero.ctaSecondary.href)}
                >
                  {hero.ctaSecondary.label}
                </Link>
              ) : null}
            </div>
          </div>
          {heroImage ? (
            <div>
              <div className="hero__photo tick">
                <Image
                  src={heroImage.src}
                  alt={heroImage.alt}
                  width={heroImage.width}
                  height={heroImage.height}
                  sizes="(max-width: 900px) 90vw, 45vw"
                  priority
                />
              </div>
              {fotoCredit ? <p className="hero__credit">{fotoCredit}</p> : null}
            </div>
          ) : (
            <div className="hero__draw tick">
              <Blueprint
                ariaLabel="Schema tecnico di un telaio in acciaio con capriata"
                withDims
              />
            </div>
          )}
        </div>
        {hero.index.length > 0 ? (
          <div className="hero__index">
            {hero.index.map((item) => (
              <div key={item.etichetta}>
                <b>{item.valore}</b>
                <span>{item.etichetta}</span>
              </div>
            ))}
          </div>
        ) : null}
      </div>
    </section>
  );
}
```

Nota: `photoOnly` senza foto E senza `Blueprint` visibile lascerebbe la home priva di un `<h1>` di pagina — accettabile in questo caso perché il piano approvato prevede che l'hero storico sia sempre e solo una foto (nessun caso reale di homepage senza `heroImage` una volta seedato), ma il fallback al disegno blueprint resta per sicurezza in ambienti di sviluppo senza WP configurato (mock già fornisce sempre `heroImage`).

- [ ] **Step 4: Aggiungi il CSS per `.hero--photo-only`/`.hero__full-photo`**

Aggiungi in coda a `web/src/styles/pages.css` (dopo il blocco `.hero__credit` esistente):

```css
/* ---------- Hero "solo foto" (fedele al sito storico) ---------- */
.hero--photo-only{ padding-block:0; }
.hero__full-photo{ position:relative; width:100%; aspect-ratio:16/7; overflow:hidden; }
.hero__full-photo img{ width:100%; height:100%; object-fit:cover; }
@media (max-width:720px){ .hero__full-photo{ aspect-ratio:4/3; } }
```

- [ ] **Step 5: Collega la modalità `photoOnly` alla home**

In `web/src/app/[locale]/page.tsx`, cambia:

```tsx
      <Hero
        hero={home.hero}
        locale={locale}
        heroImage={settings.heroImage}
        fotoCredit={settings.fotoCredit}
      />
```

in:

```tsx
      <Hero
        hero={home.hero}
        locale={locale}
        heroImage={settings.heroImage}
        photoOnly
      />
```

(rimuovi `fotoCredit` dalla chiamata: in modalità `photoOnly` il componente lo ignora comunque, ma passarlo esplicitamente sarebbe fuorviante per chi legge il chiamante).

- [ ] **Step 6: Esegui i test per verificare che passino**

Run: `cd web && npx vitest run src/components/ui/Hero.test.tsx`
Expected: PASS (4 test)

- [ ] **Step 7: Verifica tipi/lint/build**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde.

- [ ] **Step 8: Commit**

```bash
git add web/src/components/ui/Hero.tsx web/src/components/ui/Hero.test.tsx web/src/styles/pages.css web/src/app/\[locale\]/page.tsx
git commit -m "feat(hero): add photo-only mode matching the historic site, use it on the home"
```

---

### Task 2: Servizi — solo hero + elenco categorie (come il vecchio "Prodotti")

**Files:**
- Modify: `web/src/app/[locale]/servizi/page.tsx`
- Modify: `web/src/i18n/messages/it.json`
- Modify: `web/src/i18n/messages/en.json`

**Interfaces:**
- Consuma: `ServiziContent` (esistente, invariato — il campo `tipologie` resta l'unico letto da questa pagina; `processo`/`vantaggi`/`callout`/`cta` restano nello schema/nel CMS ma non vengono più letti qui).

- [ ] **Step 1: Riscrivi la pagina**

Sostituisci il contenuto di `web/src/app/[locale]/servizi/page.tsx` con:

```tsx
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { Reveal } from "@/components/ui/Reveal";
import { CategoryIndex, SectionLabel } from "@/components/ui/blocks";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("servizi"),
    siteName: t("siteName"),
    heading: t("serviziHeading"),
    description: t("serviziDescription"),
  });
}

export default async function ServiziPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const page = await getPage({ locale, key: "servizi" });
  const servizi = page?.servizi;
  if (!page || !servizi) notFound();

  const t = await getTranslations("Servizi");

  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: t("current"), url: absoluteUrl(localePath(locale, "servizi")) },
  ]);

  return (
    <>
      <JsonLd data={breadcrumbJsonLd} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">{t("current")}</span>
          </nav>
          <h1>{page.title}.</h1>
          {page.subtitle ? <p>{page.subtitle}</p> : null}
        </div>
      </section>

      {/* Elenco categorie (come il vecchio "Prodotti") */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("tipologieKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{t("tipologieTitle")}</h2>
              <p>{t("tipologieSub")}</p>
            </Reveal>
            <Reveal className="mt-6">
              <CategoryIndex categorie={servizi.tipologie} locale={locale} />
            </Reveal>
          </div>
        </div>
      </section>
    </>
  );
}
```

- [ ] **Step 2: Rimuovi le chiavi i18n non più usate**

In `web/src/i18n/messages/it.json`, dentro l'oggetto `"Servizi"`, rimuovi `processoKick`, `processoTitle`, `processoSub`, `vantaggiKick`, `vantaggiTitle` (restano `breadcrumbHome`, `current`, `tipologieKick`, `tipologieTitle`, `tipologieSub`). Applica la stessa rimozione in `web/src/i18n/messages/en.json` (stesse chiavi, copia inglese).

- [ ] **Step 3: Verifica**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde. `npm run build` deve continuare a generare `/it/servizi` ed `/en/servizi` senza errori.

- [ ] **Step 4: Commit**

```bash
git add web/src/app/\[locale\]/servizi/page.tsx web/src/i18n/messages/it.json web/src/i18n/messages/en.json
git commit -m "feat(servizi): strip page down to hero + category list, matching the historic 'Prodotti' page"
```

---

### Task 3: Azienda — solo hero + storia (come il vecchio "Chi siamo")

**Files:**
- Modify: `web/src/app/[locale]/azienda/page.tsx`
- Modify: `web/src/i18n/messages/it.json`
- Modify: `web/src/i18n/messages/en.json`

**Interfaces:**
- Consuma: `AziendaContent` (esistente, invariato — solo `storiaTitolo`/`storia` restano letti da questa pagina; `stats`/`valori`/`officinaTitolo`/`officinaSubtitle`/`officina`/`sedeTitolo`/`zona`/`comeArrivare`/`cta` restano nello schema/nel CMS ma non più letti qui).

- [ ] **Step 1: Riscrivi la pagina**

Sostituisci il contenuto di `web/src/app/[locale]/azienda/page.tsx` con:

```tsx
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage, getSettings } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  buildOrganizationJsonLd,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { Reveal } from "@/components/ui/Reveal";
import { SectionLabel } from "@/components/ui/blocks";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("azienda"),
    siteName: t("siteName"),
    heading: t("aziendaHeading"),
    description: t("aziendaDescription"),
  });
}

export default async function AziendaPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const [page, settings] = await Promise.all([
    getPage({ locale, key: "azienda" }),
    getSettings({ locale }),
  ]);
  const azienda = page?.azienda;
  if (!page || !azienda) notFound();

  const t = await getTranslations("Azienda");

  const orgJsonLd = buildOrganizationJsonLd({
    settings,
    url: absoluteUrl(localePath(locale, "azienda")),
    logoUrl: absoluteUrl("/logo-edilmetal.png"),
    type: "LocalBusiness",
    withPlaceData: true,
  });
  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: t("current"), url: absoluteUrl(localePath(locale, "azienda")) },
  ]);

  return (
    <>
      <JsonLd data={[orgJsonLd, breadcrumbJsonLd]} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">{t("current")}</span>
          </nav>
          <h1>{page.title}.</h1>
          {page.subtitle ? <p>{page.subtitle}</p> : null}
        </div>
      </section>

      {/* Storia (come il vecchio "LA SOCIETA'") */}
      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("storiaKick")} />
          <div>
            <Reveal className="sec-head">
              <h2>{azienda.storiaTitolo}</h2>
            </Reveal>
            {azienda.storia.map((par, i) => (
              <Reveal key={i} className="mt-4" style={{ color: "var(--ink-2)" }}>
                <p>{par}</p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
```

Nota per l'implementer: verifica la firma di `Reveal` in `web/src/components/ui/Reveal.tsx` — se non accetta una prop `style`, applica lo stile inline al `<p>` interno invece che al `Reveal` (es. `<Reveal key={i} className="mt-4"><p style={{ color: "var(--ink-2)" }}>{par}</p></Reveal>`), mantenendo lo stesso colore testo già usato nella versione precedente della pagina.

- [ ] **Step 2: Rimuovi le chiavi i18n non più usate**

In `web/src/i18n/messages/it.json`, dentro l'oggetto `"Azienda"`, rimuovi `valoriKick`, `valoriTitle`, `officinaKick`, `sedeKick`, `indirizzo`, `zona`, `telefono`, `email`, `orari`, `contattaci` (restano `breadcrumbHome`, `current`, `storiaKick`). Applica la stessa rimozione in `web/src/i18n/messages/en.json`.

- [ ] **Step 3: Verifica**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde. `npm run build` deve continuare a generare `/it/azienda` ed `/en/azienda` senza errori.

- [ ] **Step 4: Commit**

```bash
git add web/src/app/\[locale\]/azienda/page.tsx web/src/i18n/messages/it.json web/src/i18n/messages/en.json
git commit -m "feat(azienda): strip page down to hero + storia, matching the historic 'Chi siamo' page"
```

---

### Task 4: Contatti — rimuovi la mappa

**Files:**
- Modify: `web/src/app/[locale]/contatti/page.tsx`

**Interfaces:** Nessuna nuova interfaccia — solo rimozione di markup.

- [ ] **Step 1: Rimuovi il blocco mappa**

In `web/src/app/[locale]/contatti/page.tsx`, rimuovi l'import:

```ts
import { SiteMap } from "@/components/ui/SiteMap";
```

e rimuovi il blocco:

```tsx
              <Reveal>
                <SiteMap
                  lat={settings.coordinate.lat}
                  lng={settings.coordinate.lng}
                  label={settings.indirizzo}
                  mapsUrl={settings.mapsUrl}
                />
              </Reveal>
```

dentro `<aside className="ct-info">`. Il resto della pagina (`RequestForm` + card "Contatti diretti" con telefono/email/indirizzo/orari) resta invariato — corrisponde al blocco "Informazioni sul contatto" del vecchio sito, che va mantenuto.

Nota per l'implementer: dopo la rimozione, `<aside className="ct-info">` conterrà solo il `Reveal` con `.ct-card`. Verifica in `web/src/styles/contatti.css` se `.ct-layout`/`.ct-info` presuppongono 2 elementi diretti (form + card) — se lo stile dipendeva dal secondo blocco mappa per il layout a colonna, verifica visivamente (o leggendo il CSS) che l'aside non collassi in modo strano con un solo elemento; se necessario un piccolo aggiustamento CSS è nel perimetro di questo task (es. `align-self`/`height` sull'unico `.ct-card` rimasto), ma non introdurre nuovi blocchi di contenuto.

- [ ] **Step 2: Verifica**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde.

- [ ] **Step 3: Commit**

```bash
git add web/src/app/\[locale\]/contatti/page.tsx
git commit -m "feat(contatti): remove the map, matching the historic 'Contattaci' page"
```

---

### Task 5: Realizzazioni — solo filtro categoria (come il vecchio sito)

**Files:**
- Modify: `web/src/components/ui/RealizzazioniView.tsx`
- Modify: `web/src/components/ui/RealizzazioniView.test.tsx`
- Modify: `web/src/components/ui/RealizzazioniViewFromQuery.tsx`
- Modify: `web/src/components/ui/RealizzazioniViewFromQuery.test.tsx`
- Modify: `web/src/app/[locale]/realizzazioni/page.tsx`
- Modify: `web/src/lib/mappers/progetto.ts`
- Modify: `web/src/styles/realizzazioni.css`
- Modify: `web/src/i18n/messages/it.json`
- Modify: `web/src/i18n/messages/en.json`

**Interfaces:**
- Produce: `RealizzazioniView({ summaries, categorie, locale, initialCategoria? })` — rimossi `settori: string[]` e `anni: number[]`. `RealizzazioniViewFromQuery({ summaries, categorie, locale })` — stessa rimozione.
- Consuma: `categorieFrom(summaries)` (invariato, da `@/lib/mappers/progetto`).

- [ ] **Step 1: Aggiorna il test di `RealizzazioniView`**

Sostituisci il contenuto di `web/src/components/ui/RealizzazioniView.test.tsx` con:

```tsx
import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { RealizzazioniView } from "./RealizzazioniView";
import messages from "@/i18n/messages/it.json";

const summaries = [
  {
    id: "1",
    slug: "aiassa",
    titolo: "Aiassa",
    cliente: "Aiassa Costruzioni",
    luogo: "Piacenza (PC)",
    anno: 2018,
    categoria: { slug: "strutture-miste" as const, nome: "Strutture miste" },
    inEvidenza: false,
    copertina: { src: "/a.jpg", width: 1200, height: 900, alt: "Aiassa" },
  },
  {
    id: "2",
    slug: "acetum",
    titolo: "Acetum",
    cliente: "Acetum",
    luogo: "Parma (PR)",
    anno: 2018,
    categoria: { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
    inEvidenza: false,
    copertina: { src: "/b.jpg", width: 1200, height: 900, alt: "Acetum" },
  },
];
const categorie = [
  { slug: "strutture-miste" as const, nome: "Strutture miste" },
  { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
];

describe("RealizzazioniView", () => {
  it("pre-filters by the given initialCategoria", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView
          summaries={summaries}
          categorie={categorie}
          locale="it"
          initialCategoria="strutture-miste"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.queryByText("Acetum")).not.toBeInTheDocument();
  });

  it("shows every project when no category is selected", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView summaries={summaries} categorie={categorie} locale="it" />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.getByText("Acetum")).toBeInTheDocument();
  });
});
```

- [ ] **Step 2: Esegui i test per verificare il fallimento (le prop `settori`/`anni` erano richieste, ora tolte dal chiamante ma non ancora dal tipo)**

Run: `cd web && npx vitest run src/components/ui/RealizzazioniView.test.tsx`
Expected: FAIL — errore di tipo TS (`settori`/`anni` mancanti secondo il tipo attuale) o comportamento invariato col filtro non ancora semplificato.

- [ ] **Step 3: Riscrivi `RealizzazioniView.tsx`**

Sostituisci il contenuto di `web/src/components/ui/RealizzazioniView.tsx` con:

```tsx
"use client";

import { useEffect, useMemo, useState } from "react";
import { useTranslations } from "next-intl";
import type { Categoria, CategoriaSlug, ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { ProjectCard } from "./ProjectCard";

type RealizzazioniViewProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  locale: Locale;
  /** Categoria pre-selezionata (es. da un link "vai alla sezione prodotti"). */
  initialCategoria?: CategoriaSlug;
};

const filterIcon = (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
    <path d="M4 6h16M7 12h10M10 18h4" />
  </svg>
);
const closeIcon = (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
    <path d="M18 6 6 18M6 6l12 12" />
  </svg>
);

/**
 * Portfolio realizzazioni filtrabile per sola categoria (fedele all'IA del
 * sito storico: una vista per categoria). Nessun filtro settore/anno, nessun
 * ordinamento, nessuna paginazione — griglia unica con tutti i risultati
 * della categoria scelta (o tutte le realizzazioni se nessuna è selezionata).
 */
export function RealizzazioniView({
  summaries,
  categorie,
  locale,
  initialCategoria,
}: RealizzazioniViewProps) {
  const t = useTranslations("Realizzazioni");
  const [cat, setCat] = useState<CategoriaSlug | null>(initialCategoria ?? null);
  const [drawerOpen, setDrawerOpen] = useState(false);

  useEffect(() => {
    document.body.classList.toggle("filters-open", drawerOpen);
    return () => document.body.classList.remove("filters-open");
  }, [drawerOpen]);

  useEffect(() => {
    if (!drawerOpen) return;
    function onKey(e: KeyboardEvent) {
      if (e.key === "Escape") setDrawerOpen(false);
    }
    document.addEventListener("keydown", onKey);
    return () => document.removeEventListener("keydown", onKey);
  }, [drawerOpen]);

  const pickCat = (value: CategoriaSlug | null) => setCat(value);

  const filtered = useMemo(() => {
    const out = cat ? summaries.filter((p) => p.categoria.slug === cat) : summaries.slice();
    return out.sort((a, b) => b.anno - a.anno || a.titolo.localeCompare(b.titolo, "it"));
  }, [summaries, cat]);

  const reset = () => setCat(null);

  return (
    <>
      <div className="rz-backdrop" aria-hidden="true" onClick={() => setDrawerOpen(false)} />

      <form className="rz-filters" aria-label={t("filters")} onSubmit={(e) => e.preventDefault()}>
        <div className="rz-filters__head">
          <h2>{t("filters")}</h2>
          <button type="button" className="icon-btn" aria-label={t("closeFilters")} onClick={() => setDrawerOpen(false)}>
            {closeIcon}
          </button>
        </div>

        <div className="rz-fgroup">
          <span className="rz-fgroup__lbl" id="lbl-cat">{t("categoria")}</span>
          <div className="chips" role="group" aria-labelledby="lbl-cat">
            <button type="button" className="chip" aria-pressed={cat === null} onClick={() => pickCat(null)}>{t("tutte")}</button>
            {categorie.map((c) => (
              <button key={c.slug} type="button" className="chip" aria-pressed={cat === c.slug} onClick={() => pickCat(c.slug)}>{c.nome}</button>
            ))}
          </div>
        </div>

        <button type="button" className="btn btn--deep rz-filters__apply" onClick={() => setDrawerOpen(false)}>
          {t("apply")}
        </button>
      </form>

      <div className="rz-toolbar">
        <span className="rz-count" aria-live="polite">
          {t("countFound", { count: filtered.length })}
        </span>
        <button
          type="button"
          className="rz-filters-btn"
          aria-expanded={drawerOpen}
          onClick={() => setDrawerOpen(true)}
        >
          {filterIcon}
          {t("openFilters")}
        </button>
      </div>

      {filtered.length > 0 ? (
        <div className="proj-grid" style={{ marginTop: "var(--sp-6)" }}>
          {filtered.map((p) => (
            <ProjectCard key={p.id} progetto={p} locale={locale} />
          ))}
        </div>
      ) : (
        <div className="rz-empty">
          <h3>{t("emptyTitle")}</h3>
          <p>{t("emptyText")}</p>
          <button type="button" className="btn btn--outline" style={{ marginTop: "var(--sp-4)" }} onClick={reset}>
            {t("resetFilters")}
          </button>
        </div>
      )}
    </>
  );
}
```

- [ ] **Step 4: Aggiorna `RealizzazioniViewFromQuery.tsx`**

Sostituisci il contenuto con:

```tsx
"use client";

import { useSearchParams } from "next/navigation";
import { categoriaSlugSchema, type Categoria, type ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { RealizzazioniView } from "./RealizzazioniView";

type RealizzazioniViewFromQueryProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  locale: Locale;
};

/**
 * Adatta `RealizzazioniView` all'export statico: non esiste un server in
 * grado di leggere `?categoria=` a request-time, quindi la query string va
 * letta lato client (`useSearchParams`, valida solo dopo l'idratazione) e
 * validata con lo schema di dominio prima di pre-selezionare la categoria.
 * Il chiamante deve avvolgere questo componente in `<Suspense>`, come
 * richiesto da Next.js per `useSearchParams` in un export statico.
 */
export function RealizzazioniViewFromQuery(props: RealizzazioniViewFromQueryProps) {
  const searchParams = useSearchParams();
  const parsedCategoria = categoriaSlugSchema.safeParse(searchParams.get("categoria") ?? undefined);
  const initialCategoria = parsedCategoria.success ? parsedCategoria.data : undefined;

  return <RealizzazioniView {...props} initialCategoria={initialCategoria} />;
}
```

- [ ] **Step 5: Aggiorna il test di `RealizzazioniViewFromQuery`**

In `web/src/components/ui/RealizzazioniViewFromQuery.test.tsx`, rimuovi `settori={[]}` e `anni={[2018]}` dalle due chiamate dentro `renderFromQuery()` (la funzione `RealizzazioniViewFromQuery` ora ha solo `summaries`/`categorie`/`locale`).

- [ ] **Step 6: Aggiorna `realizzazioni/page.tsx`**

Sostituisci il contenuto di `web/src/app/[locale]/realizzazioni/page.tsx` con:

```tsx
import type { Metadata } from "next";
import Link from "next/link";
import { Suspense } from "react";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getProgetti } from "@/lib/api";
import { categorieFrom } from "@/lib/mappers/progetto";
import {
  JsonLd,
  absoluteUrl,
  buildBreadcrumbJsonLd,
  buildMetadata,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { RealizzazioniView } from "@/components/ui/RealizzazioniView";
import { RealizzazioniViewFromQuery } from "@/components/ui/RealizzazioniViewFromQuery";
import { Reveal } from "@/components/ui/Reveal";
import { CtaBand, SectionLabel } from "@/components/ui/blocks";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor("realizzazioni"),
    siteName: t("siteName"),
    heading: t("realizzazioniHeading"),
    description: t("realizzazioniDescription"),
  });
}

export default async function RealizzazioniPage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const summaries = await getProgetti({ locale });
  const t = await getTranslations("Realizzazioni");
  const tSeo = await getTranslations("SEO");
  const tNav = await getTranslations("Nav");

  const categorie = categorieFrom(summaries);

  const breadcrumbJsonLd = buildBreadcrumbJsonLd([
    { name: t("breadcrumbHome"), url: absoluteUrl(localePath(locale)) },
    { name: tSeo("realizzazioniHeading"), url: absoluteUrl(localePath(locale, "realizzazioni")) },
  ]);

  return (
    <>
      <JsonLd data={breadcrumbJsonLd} />

      <section className="page-head">
        <div className="container">
          <nav className="crumbs" aria-label="Percorso">
            <Link href={`/${locale}`}>{t("breadcrumbHome")}</Link>
            <span className="sep">/</span>
            <span aria-current="page">{t("current")}</span>
          </nav>
          <h1>{tSeo("realizzazioniHeading")}.</h1>
          <p>{tSeo("realizzazioniDescription")}</p>
        </div>
      </section>

      <section className="section">
        <div className="container sec-grid">
          <SectionLabel num="01" kick={t("portfolioKick")} />
          <div>
            <Suspense
              fallback={<RealizzazioniView summaries={summaries} categorie={categorie} locale={locale} />}
            >
              <RealizzazioniViewFromQuery
                summaries={summaries}
                categorie={categorie}
                locale={locale}
              />
            </Suspense>
          </div>
        </div>
      </section>

      <section className="section section--alt">
        <div className="container">
          <Reveal>
            <CtaBand
              titolo={t("ctaTitle")}
              testo={t("ctaText")}
              ctaLabel={tNav("ctaPreventivo")}
              ctaHref={`/${locale}/contatti`}
            />
          </Reveal>
        </div>
      </section>
    </>
  );
}
```

- [ ] **Step 7: Rimuovi `settoriFrom`/`anniFrom` da `lib/mappers/progetto.ts`**

In `web/src/lib/mappers/progetto.ts`, rimuovi le funzioni `settoriFrom` e `anniFrom` (nessun altro file le usa più dopo lo Step 6 — verifica con `grep -rn "settoriFrom\|anniFrom" web/src` prima di rimuoverle: deve restituire zero risultati dopo questo step).

- [ ] **Step 8: Pulisci il CSS non più usato**

In `web/src/styles/realizzazioni.css`, rimuovi:
- Le righe `.rz-sort{...}`, `.rz-sort label{...}`, `.rz-sort .select{...}` (blocco "Toolbar").
- L'intero blocco "Paginazione" (`.pager{...}` e le 4 righe seguenti).
- La riga `.rz-sort .select{ min-width:0; }` dentro `@media (max-width:620px)`.

Non toccare `.rz-fgroup`, `.chips`, `.chip`, `.rz-filters*`, `.rz-backdrop`, `.rz-toolbar`, `.rz-count`, `.proj-grid` — restano tutti in uso.

- [ ] **Step 9: Rimuovi le chiavi i18n non più usate**

In `web/src/i18n/messages/it.json`, dentro l'oggetto `"Realizzazioni"`, rimuovi `settore`, `anno`, `tutti`, `sortLabel`, `sortRecent`, `sortOldest`, `sortCategoria`, `sortCliente` (restano `breadcrumbHome`, `current`, `portfolioKick`, `filters`, `closeFilters`, `categoria`, `tutte`, `countFound`, `apply`, `openFilters`, `emptyTitle`, `emptyText`, `resetFilters`, `ctaTitle`, `ctaText`). Applica la stessa rimozione in `web/src/i18n/messages/en.json`.

- [ ] **Step 10: Esegui i test per verificare che passino**

Run: `cd web && npx vitest run src/components/ui/RealizzazioniView.test.tsx src/components/ui/RealizzazioniViewFromQuery.test.tsx`
Expected: PASS.

- [ ] **Step 11: Verifica completa**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde. Verifica anche a occhio in `web/out/it/realizzazioni/index.html` (dopo il build) che non compaiano più riferimenti a paginazione/ordinamento nel markup generato.

- [ ] **Step 12: Commit**

```bash
git add web/src/components/ui/RealizzazioniView.tsx web/src/components/ui/RealizzazioniView.test.tsx web/src/components/ui/RealizzazioniViewFromQuery.tsx web/src/components/ui/RealizzazioniViewFromQuery.test.tsx web/src/app/\[locale\]/realizzazioni/page.tsx web/src/lib/mappers/progetto.ts web/src/styles/realizzazioni.css web/src/i18n/messages/it.json web/src/i18n/messages/en.json
git commit -m "feat(realizzazioni): drop settore/anno filters, sort and pagination, keep category-only view"
```

---

### Task 6: Sweep finale — componenti e chiavi i18n ormai orfani

**Files:**
- Modify: `web/src/components/ui/blocks.tsx`

**Interfaces:** Nessuna — solo rimozione di codice morto, verificata con `grep` prima di ogni cancellazione.

- [ ] **Step 1: Verifica quali blocchi sono ora orfani**

Esegui, dalla root del repo:

```bash
for c in Flow Feats StatsRow Callout CategoryIndex CtaBand Rail SiteMap; do
  echo "== $c =="
  grep -rln "<$c\b" web/src/app web/src/components | grep -v "\.test\."
done
```

Atteso dopo i Task 1-5: `Flow`, `Feats`, `StatsRow`, `Callout` → nessun risultato (orfani). `CategoryIndex` → ancora usato in `servizi/page.tsx`. `CtaBand` → ancora usato in `realizzazioni/page.tsx`. `Rail` → ancora usato in `realizzazioni/[slug]/page.tsx` e `RealizzazioniBand.tsx`. `SiteMap` → nessun risultato (orfano, ma vedi nota sotto).

- [ ] **Step 2: Rimuovi `Flow`, `Feats`, `StatsRow`, `Callout` da `blocks.tsx` — SOLO se il grep dello Step 1 conferma zero usi**

In `web/src/components/ui/blocks.tsx`, rimuovi le funzioni `Flow`, `Feats`, `StatsRow`, `Callout` (e gli import/costanti usati solo da loro, es. `FEAT_ICONS`/`FeatIcon` se non più referenziati da nessun'altra funzione del file — verifica con una lettura del file prima di toccare gli import condivisi). Non rimuovere `CategoryIndex`, `CtaBand`, `SectionLabel`, `SecHead`, `Clients` (se già rimosso in un lavoro precedente, ignora) — verifica sempre con grep prima di cancellare, il grep è la fonte di verità, non questa lista.

- [ ] **Step 3: Lascia `SiteMap.tsx` intatto (non cancellare il file)**

`web/src/components/ui/SiteMap.tsx` diventa orfano ma NON va cancellato in questo task: è un componente Leaflet non banale (integrazione mappa) e la sua rimozione tocca le dipendenze `leaflet`/`react-leaflet` in `package.json` — una decisione più ampia, fuori dal perimetro di "fedeltà al sito storico". Lascialo com'è, senza uso, per un'eventuale pulizia futura separata.

- [ ] **Step 4: Verifica**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde.

- [ ] **Step 5: Commit**

```bash
git add web/src/components/ui/blocks.tsx
git commit -m "chore: remove Flow/Feats/StatsRow/Callout, orphaned after the historic-fidelity strip-down"
```

---

## Self-Review (svolta durante la stesura)

- **Copertura spec**: Hero (Task 1) ✓, Servizi (Task 2) ✓, Azienda (Task 3) ✓, Contatti (Task 4) ✓, Realizzazioni (Task 5) ✓, pulizia codice morto conseguente (Task 6) ✓.
- **Coerenza dei tipi tra task**: `RealizzazioniViewProps`/`RealizzazioniViewFromQueryProps` definiti nel Task 5 e usati identicamente in `page.tsx` nello stesso task — nessuna dipendenza da nomi introdotti in altri task. `Hero`'s `photoOnly` (Task 1) non è consumato da nessun altro task.
- **Nessun placeholder**: ogni step ha codice completo o comando+esito atteso espliciti.
