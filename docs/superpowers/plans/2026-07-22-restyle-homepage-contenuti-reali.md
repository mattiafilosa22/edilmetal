# Restyle homepage su struttura sito storico + contenuti reali — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the Edilmetal homepage around the block sequence of the 2024 archived site (hero photo → "In evidenza" 2 categories → "Realizzazioni" dark band → Contatti/Orari/Telefono → footer), reinterpreted in the current "blueprint" visual language, and populate the whole site (galleries, hero, category teasers) with the real 1997–2018 project photos found in `sito web edilmetal 2018/`.

**Architecture:** No structural change to the stack (WordPress headless + Next.js static export, REST `edilmetal/v1/*`, CPT `progetto` / taxonomy `categoria_opera`). Work is additive/corrective on top of the existing domain types, Meta Box fields, PHP presenters, seeders and React components — several of which are discovered mid-plan to be already half-wired (e.g. `SiteSettings.heroImage` exposed by PHP but not in the zod schema) or entirely disconnected (Home's `processo`/`perche`/`referenze`/`cta` blocks exist only in the frontend mock, never in the real PHP presenter).

**Tech Stack:** Next.js (App Router, TS strict, Server Components), zod, next-intl, Vitest + Testing Library. WordPress PHP 8, Meta Box (free), Polylang, WP-CLI seeder (`wp edilmetal seed`).

## Global Constraints

- TS strict, no `any`; zod validates all REST DTOs at the boundary (see `web/src/domain/*`).
- PHP: WordPress Coding Standards + PSR-12, `declare(strict_types=1)`, REST via `permission_callback`, no direct SQL.
- No content hardcoded in the frontend beyond UI labels (`web/src/i18n/messages/*.json`); all editorial copy comes from WordPress.
- Only free WP plugins (Meta Box free, Polylang free, Fluent Forms free).
- Accessibility WCAG 2.1 AA is definition-of-done for every UI task (alt text on all real photos is mandatory, not optional).
- Compatible with Next.js static export (`output: 'export'`); no server-only APIs in page components beyond what's already used.
- Local-only in this pass: no changes to the GitHub Actions deploy workflow; verify in the Docker dev environment (`npm run dev:up` / `npm run dev:setup`) before any deploy is considered.

---

## Part A — Domain contracts (frontend, TDD, no WP required)

### Task 1: `SiteSettings` — add `heroImage` and `fotoCredit`

The PHP `SettingsPresenter::to_dto()` (`cms/mu-plugins/edilmetal-core/rest/Presenters/SettingsPresenter.php:75-82`) already emits `heroImage` (Image DTO) and `fotoCredit` (string) when the WP fields `edilmetal_set_hero_image` / `edilmetal_set_foto_credit` are set — but the frontend zod schema doesn't declare them, so `zod`'s default parsing silently strips both keys. This task closes that gap. A new `fax` field is also added (present on the old site footer, absent from the current contract).

**Files:**
- Modify: `web/src/domain/settings.ts`
- Test: `web/src/domain/settings.test.ts` (new)

**Interfaces:**
- Produces: `SiteSettings.heroImage?: Image`, `SiteSettings.fotoCredit?: string`, `SiteSettings.fax?: string` (all optional, all string/Image already exported from `./progetto`).

- [ ] **Step 1: Write the failing test**

```ts
// web/src/domain/settings.test.ts
import { describe, expect, it } from "vitest";
import { siteSettingsSchema } from "./settings";

const base = {
  nomeAzienda: "Edilmetal",
  ragioneSociale: "Edilmetal S.r.l.",
  partitaIva: "01234567890",
  indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
  telefono: "0521 615023",
  email: "info@edilmetal.it",
  coordinate: { lat: 44.8103, lng: 10.1747 },
  orari: [],
  social: {},
};

describe("siteSettingsSchema", () => {
  it("accepts heroImage, fotoCredit and fax when present", () => {
    const parsed = siteSettingsSchema.parse({
      ...base,
      fax: "0521 615207",
      fotoCredit: "Archivio fotografico Edilmetal",
      heroImage: {
        src: "https://cms.edilmetal.it/wp-content/uploads/parmalat-1.jpg",
        width: 4160,
        height: 2336,
        alt: "Montaggio di una struttura in acciaio per Parmalat",
      },
    });
    expect(parsed.fax).toBe("0521 615207");
    expect(parsed.heroImage?.width).toBe(4160);
  });

  it("parses fine when heroImage/fotoCredit/fax are absent", () => {
    const parsed = siteSettingsSchema.parse(base);
    expect(parsed.heroImage).toBeUndefined();
    expect(parsed.fax).toBeUndefined();
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd web && npx vitest run src/domain/settings.test.ts`
Expected: FAIL — `parsed.fax`/`parsed.heroImage` are `undefined` in the first case too because zod strips unknown keys silently (test fails on `expect(parsed.fax).toBe(...)`), OR a type error at compile time if `fax`/`heroImage` aren't declared on the inferred type (`Property 'fax' does not exist`).

- [ ] **Step 3: Implement**

```ts
// web/src/domain/settings.ts — add import + fields to siteSettingsSchema
import { z } from "zod";
import { imageSchema } from "./progetto";

// ...existing orarioSchema / socialSchema / coordinateSchema unchanged...

export const siteSettingsSchema = z.object({
  nomeAzienda: z.string().min(1),
  ragioneSociale: z.string().min(1),
  partitaIva: z.string().min(1),
  indirizzo: z.string().min(1),
  telefono: z.string().min(1),
  /** Fax (opzionale, dato storico del sito precedente). */
  fax: z.string().optional(),
  email: z.email(),
  coordinate: coordinateSchema,
  mapsUrl: z.string().optional(),
  orari: z.array(orarioSchema).default([]),
  social: socialSchema.default({}),
  /** Foto hero della homepage, editabile globalmente in WP (Impostazioni). */
  heroImage: imageSchema.optional(),
  /** Credito fotografico mostrato accanto all'hero/footer. */
  fotoCredit: z.string().optional(),
});
export type SiteSettings = z.infer<typeof siteSettingsSchema>;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `cd web && npx vitest run src/domain/settings.test.ts`
Expected: PASS (2 tests)

- [ ] **Step 5: Commit**

```bash
git add web/src/domain/settings.ts web/src/domain/settings.test.ts
git commit -m "feat(domain): expose heroImage, fotoCredit and fax on SiteSettings"
```

---

### Task 2: `HomeContent` — replace unused blocks with `hero.ctaPrimary`/`ctaSecondary`/`index` and `inEvidenza`

`homeContentSchema` today declares `stats`, `statsIntro`, `processo`, `perche`, `referenze`, `cta` — none of which the PHP presenter (`PagePresenter::to_dto()` case `'home'`) ever populates; they only exist in the frontend mock (`web/src/lib/api/mock/pages.ts`). Per the approved design, these sections are removed from the home page. In their place: a fully-specified `hero` (the existing `homeHeroSchema` in `web/src/domain/page.ts:71-81` is correct and stays, it's the PHP side that was never wired — see Task 6) and a new `inEvidenza` block (the "In evidenza" 2-category teaser).

**Files:**
- Modify: `web/src/domain/page.ts`
- Test: `web/src/domain/page.test.ts` (new)

**Interfaces:**
- Consumes: `categoriaSchema` (from `./progetto`, shape `{slug, nome}`), `imageSchema` (from `./progetto`).
- Produces: `homeFeaturedSchema` → `HomeFeatured = { categoria: Categoria; immagine: Image }`. `homeContentSchema` → `HomeContent = { hero: HomeHero; inEvidenza: HomeFeatured[] }` (`inEvidenza` has `.max(2)`, `.default([])`).

- [ ] **Step 1: Write the failing test**

```ts
// web/src/domain/page.test.ts
import { describe, expect, it } from "vitest";
import { homeContentSchema } from "./page";

const hero = {
  title: "Strutture in acciaio",
  subtitle: "Progettazione, produzione e montaggio su commessa.",
  ctaPrimary: { label: "Le realizzazioni", href: "/realizzazioni" },
  index: [],
};

describe("homeContentSchema", () => {
  it("accepts hero + up to 2 featured categories", () => {
    const parsed = homeContentSchema.parse({
      hero,
      inEvidenza: [
        {
          categoria: { slug: "strutture-acciaio", nome: "Strutture in acciaio" },
          immagine: { src: "/a.jpg", width: 1200, height: 800, alt: "Struttura in acciaio" },
        },
      ],
    });
    expect(parsed.inEvidenza).toHaveLength(1);
  });

  it("rejects a third featured category", () => {
    const three = Array.from({ length: 3 }, (_, i) => ({
      categoria: { slug: "scale", nome: "Scale" },
      immagine: { src: `/${i}.jpg`, width: 1200, height: 800, alt: "Scala" },
    }));
    expect(() => homeContentSchema.parse({ hero, inEvidenza: three })).toThrow();
  });

  it("no longer accepts the old stats/processo/perche/referenze/cta shape as required", () => {
    const parsed = homeContentSchema.parse({ hero });
    expect(parsed.inEvidenza).toEqual([]);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd web && npx vitest run src/domain/page.test.ts`
Expected: FAIL — `homeFeaturedSchema`/updated `homeContentSchema` don't exist yet (TS error) or the old schema requires `statsIntro`/`cta` and rejects the minimal payload.

- [ ] **Step 3: Implement**

```ts
// web/src/domain/page.ts
// Replace the whole "HOME" section (lines ~67-93) with:

/* -------------------------------------------------------------------------- */
/* HOME                                                                       */
/* -------------------------------------------------------------------------- */

export const homeHeroSchema = z.object({
  eyebrow: z.string().optional(),
  title: z.string().min(1),
  titleAccent: z.string().optional(),
  subtitle: z.string().min(1),
  ctaPrimary: ctaSchema,
  ctaSecondary: ctaSchema.optional(),
  /** Barra-indice mono sotto l'hero (max 3 voci). */
  index: z.array(statSchema).default([]),
});
export type HomeHero = z.infer<typeof homeHeroSchema>;

/** Categoria "in evidenza" in home: foto reale + link alla sezione prodotti. */
export const homeFeaturedSchema = z.object({
  categoria: categoriaSchema,
  immagine: imageSchema,
});
export type HomeFeatured = z.infer<typeof homeFeaturedSchema>;

export const homeContentSchema = z.object({
  hero: homeHeroSchema,
  /** "In evidenza": fino a 2 categorie con foto reale (blocco storico del sito). */
  inEvidenza: z.array(homeFeaturedSchema).max(2).default([]),
});
export type HomeContent = z.infer<typeof homeContentSchema>;
```

`categoriaSchema` is the `{slug, nome}` shape already exported from `progetto.ts` (used by `ProgettoSummary.categoria`) — reuse it rather than declaring a new shape.

And update the import line at the top of the file:

```ts
import { z } from "zod";
import { categoriaSchema, categoriaSlugSchema, imageSchema, seoMetaSchema } from "./progetto";
```

(`categoriaSlugSchema` was already imported; add `categoriaSchema` and `imageSchema` alongside it. Remove the now-unused `categoriaRefSchema`-based `categorie` field — `categoriaRefSchema` itself stays, `servizi.tipologie` still uses it.)

- [ ] **Step 4: Run test to verify it passes**

Run: `cd web && npx vitest run src/domain/page.test.ts`
Expected: PASS (3 tests)

- [ ] **Step 5: Run the full typecheck to catch every place the removed fields were read**

Run: `cd web && npm run typecheck`
Expected: FAIL, listing every usage of `home.stats`, `home.statsIntro`, `home.categorie`, `home.processo`, `home.perche`, `home.referenze`, `home.cta` in `web/src/app/[locale]/page.tsx` and `web/src/lib/api/mock/pages.ts`. This is expected — Tasks 4 and 9 fix those call sites. Do not fix them here; just confirm the errors are exactly the ones expected (no unrelated breakage) before moving on.

- [ ] **Step 6: Commit**

```bash
git add web/src/domain/page.ts web/src/domain/page.test.ts
git commit -m "feat(domain): replace unused Home blocks with hero.ctaPrimary/index and inEvidenza"
```

---

### Task 3: Mock settings — add real hero photo + fax

**Files:**
- Modify: `web/src/lib/api/mock/settings.ts`
- Create: `web/public/mock/hero-parmalat.jpg` (downsized copy of the real photo, see step 1)

**Interfaces:**
- Consumes: `SiteSettings` from Task 1.

- [ ] **Step 1: Prepare the real hero photo for the mock/public path**

The archived site's homepage hero and `sito web edilmetal 2018/home page/parmalat 1.jpg` are the same shot (crane erecting a steel frame for Parmalat). Downsize it for the frontend mock fallback (used when WordPress isn't reachable) — the real WP-served version goes through WP's own image sizes (wired in Task 21, no separate copy needed there).

```bash
mkdir -p web/public/mock
sips -Z 1920 -s format jpeg -s formatOptions 82 \
  "sito web edilmetal 2018/home page/parmalat 1.jpg" \
  --out web/public/mock/hero-parmalat.jpg
```

Verify: `sips -g pixelWidth -g pixelHeight web/public/mock/hero-parmalat.jpg` → width should be 1920, height proportional (~1078).

- [ ] **Step 2: Update the mock**

```ts
// web/src/lib/api/mock/settings.ts
import type { SiteSettings } from "@/domain";

export const mockSettings: SiteSettings = {
  nomeAzienda: "Edilmetal",
  ragioneSociale: "Edilmetal S.r.l.",
  partitaIva: "00000000000",
  indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
  telefono: "0521 615023",
  fax: "0521 615207",
  email: "info@edilmetal.it",
  coordinate: { lat: 44.8103, lng: 10.1747 },
  mapsUrl:
    "https://www.google.com/maps/search/?api=1&query=Piazza+Alpini+d%27Italia+10%2FA+Noceto+PR",
  orari: [{ giorni: "Lun–Ven", apertura: "08:00–12:00 / 14:00–18:00" }],
  social: {
    linkedin: "#",
    facebook: "#",
    instagram: "#",
  },
  heroImage: {
    src: "/mock/hero-parmalat.jpg",
    width: 1920,
    height: 1078,
    alt: "Gru che monta la struttura in acciaio di un capannone Parmalat",
  },
  fotoCredit: "Archivio fotografico Edilmetal.",
};
```

- [ ] **Step 3: Verify with typecheck**

Run: `cd web && npm run typecheck`
Expected: no new errors attributable to `mock/settings.ts` (pre-existing errors from Task 2 Step 5 are still expected and fixed later).

- [ ] **Step 4: Commit**

```bash
git add web/public/mock/hero-parmalat.jpg web/src/lib/api/mock/settings.ts
git commit -m "feat(mock): real hero photo + fax in mock settings"
```

---

### Task 4: Mock pages — rewrite `home` entry

**Files:**
- Modify: `web/src/lib/api/mock/pages.ts`

**Interfaces:**
- Consumes: `HomeContent`, `HomeFeatured` from Task 2.

- [ ] **Step 1: Replace the `home` object**

Remove `statsIntro`, `stats`, `categorie`, `processo`, `perche`, `referenze`, `cta` from the `home.home` block (the `PROCESSO`/`PERCHE`/`CATEGORIE` constants stay — `servizi` and `azienda` mocks still reference them via `tipologie`/`vantaggi`/`valori` elsewhere in the same file, do not delete those constants). Replace with:

```ts
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
```

- [ ] **Step 2: Run typecheck**

Run: `cd web && npm run typecheck`
Expected: errors remaining only in `web/src/app/[locale]/page.tsx` (fixed in Task 9) and possibly `web/src/components/ui/Hero.tsx` (fixed in Task 5).

- [ ] **Step 3: Commit**

```bash
git add web/src/lib/api/mock/pages.ts
git commit -m "feat(mock): rewrite home mock to hero + inEvidenza shape"
```

---

## Part B — Frontend components

### Task 5: `Hero` — real photo instead of the abstract SVG drawing

**Files:**
- Modify: `web/src/components/ui/Hero.tsx`
- Modify: `web/src/styles/pages.css`
- Modify: `web/src/app/[locale]/page.tsx` (pass `heroImage`/`fotoCredit` — done together with Task 9, but the prop must exist now so Hero compiles standalone)
- Test: `web/src/components/ui/Hero.test.tsx` (new)

**Interfaces:**
- Consumes: `HomeHero` (Task 2), `Image` (existing, from `@/domain`).
- Produces: `Hero({ hero, locale, heroImage, fotoCredit })` — `heroImage?: Image`, `fotoCredit?: string` both optional; when `heroImage` is absent the component falls back to the existing `<Blueprint>` SVG (keeps the component usable wherever no photo is configured yet).

- [ ] **Step 1: Write the failing test**

```tsx
// web/src/components/ui/Hero.test.tsx
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
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd web && npx vitest run src/components/ui/Hero.test.tsx`
Expected: FAIL — `heroImage`/`fotoCredit` props don't exist yet, photo never renders.

- [ ] **Step 3: Implement**

```tsx
// web/src/components/ui/Hero.tsx
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
  /** Credito fotografico opzionale, mostrato sotto la foto. */
  fotoCredit?: string;
};

function withLocale(locale: Locale, href: string): string {
  return `/${locale}${href.startsWith("/") ? href : `/${href}`}`;
}

/**
 * Hero editoriale asimmetrico della home: testo + foto reale (o, in assenza,
 * disegno tecnico blueprint), con barra-indice mono. Un solo `h1` per pagina (WCAG).
 */
export function Hero({ hero, locale, heroImage, fotoCredit }: HeroProps) {
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

- [ ] **Step 4: Add CSS**

In `web/src/styles/pages.css`, right after the existing `.hero__draw{ position:relative; aspect-ratio:1/1; }` line, add:

```css
.hero__photo{ position:relative; aspect-ratio:4/3; border-radius:var(--radius-lg); overflow:hidden; }
.hero__photo img{ width:100%; height:100%; object-fit:cover; }
.hero__credit{ font-family:var(--f-mono); font-size:var(--fs-xs); color:var(--ink-faint); margin-top:var(--sp-2); text-align:right; }
```

And in the existing `@media (max-width:900px){ ... }` block, change:

```css
  .hero__draw{ max-width:440px; margin-inline:auto; order:-1; }
```

to:

```css
  .hero__draw,.hero__photo{ max-width:440px; margin-inline:auto; order:-1; }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd web && npx vitest run src/components/ui/Hero.test.tsx`
Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```bash
git add web/src/components/ui/Hero.tsx web/src/components/ui/Hero.test.tsx web/src/styles/pages.css
git commit -m "feat(hero): render real photo when available, fall back to blueprint drawing"
```

---

### Task 6: `FeaturedCategories` — new "In evidenza" 2-card block

**Files:**
- Create: `web/src/components/ui/FeaturedCategories.tsx`
- Modify: `web/src/styles/pages.css`
- Modify: `web/src/i18n/messages/it.json`, `web/src/i18n/messages/en.json`
- Test: `web/src/components/ui/FeaturedCategories.test.tsx` (new)

**Interfaces:**
- Consumes: `HomeFeatured[]` (Task 2), `Locale` (existing `@/i18n/routing`).
- Produces: `FeaturedCategories({ items, locale }: { items: HomeFeatured[]; locale: Locale })`. Renders nothing (`null`) when `items` is empty. Links to `/${locale}/realizzazioni?categoria=${slug}`.

- [ ] **Step 1: Add i18n keys**

In `web/src/i18n/messages/it.json`, inside the `"Home"` object, remove `aziendaKick`, `cosaKick`, `cosaTitle`, `cosaSub`, `processoKick`, `processoTitle`, `processoSub`, `percheKick`, `referenzeKick`, `referenzeTitle`, `referenzeSub` (all belonged to sections that no longer exist on Home) and add:

```json
"categorieKick": "In evidenza",
"categorieCtaLabel": "Vai alla sezione prodotti"
```

Keep `evidenzaKick`, `evidenzaTitle`, `evidenzaSub`, `tutteLeRealizzazioni` (repurposed for the dark "Realizzazioni" band in Task 7). In `web/src/i18n/messages/en.json`, mirror the same removals/additions with English copy:

```json
"categorieKick": "Featured",
"categorieCtaLabel": "See the product line"
```

(inspect the current `en.json` `"Home"` block first — it mirrors `it.json` key-for-key; remove/add the same keys there.)

- [ ] **Step 2: Write the failing test**

```tsx
// web/src/components/ui/FeaturedCategories.test.tsx
import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { FeaturedCategories } from "./FeaturedCategories";
import messages from "@/i18n/messages/it.json";

const items = [
  {
    categoria: { slug: "strutture-acciaio" as const, nome: "Strutture in acciaio" },
    immagine: { src: "/a.jpg", width: 1200, height: 900, alt: "Capannone in acciaio" },
  },
  {
    categoria: { slug: "pensiline" as const, nome: "Pensiline" },
    immagine: { src: "/b.jpg", width: 1200, height: 900, alt: "Pensilina industriale" },
  },
];

function renderWithIntl(ui: React.ReactElement) {
  return render(
    <NextIntlClientProvider locale="it" messages={messages}>
      {ui}
    </NextIntlClientProvider>
  );
}

describe("FeaturedCategories", () => {
  it("renders one card per featured category with a link to the filtered listing", () => {
    renderWithIntl(<FeaturedCategories items={items} locale="it" />);
    expect(screen.getByText("Strutture in acciaio")).toBeInTheDocument();
    expect(screen.getByText("Pensiline")).toBeInTheDocument();
    const links = screen.getAllByRole("link", { name: /vai alla sezione prodotti/i });
    expect(links[0]).toHaveAttribute("href", "/it/realizzazioni?categoria=strutture-acciaio");
    expect(links[1]).toHaveAttribute("href", "/it/realizzazioni?categoria=pensiline");
  });

  it("renders nothing when there are no featured categories", () => {
    const { container } = renderWithIntl(<FeaturedCategories items={[]} locale="it" />);
    expect(container).toBeEmptyDOMElement();
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `cd web && npx vitest run src/components/ui/FeaturedCategories.test.tsx`
Expected: FAIL — module doesn't exist.

- [ ] **Step 4: Implement**

```tsx
// web/src/components/ui/FeaturedCategories.tsx
import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";
import type { HomeFeatured } from "@/domain";
import type { Locale } from "@/i18n/routing";

type FeaturedCategoriesProps = {
  items: HomeFeatured[];
  locale: Locale;
};

/**
 * Blocco "In evidenza": fino a 2 categorie con foto reale, come nel sito
 * storico. Ogni card rimanda alla lista realizzazioni pre-filtrata.
 */
export function FeaturedCategories({ items, locale }: FeaturedCategoriesProps) {
  const t = useTranslations("Home");

  if (items.length === 0) {
    return null;
  }

  return (
    <div className="featured">
      {items.map((item) => (
        <Link
          key={item.categoria.slug}
          className="featured__card"
          href={`/${locale}/realizzazioni?categoria=${item.categoria.slug}`}
        >
          <Image
            src={item.immagine.src}
            alt={item.immagine.alt}
            fill
            sizes="(max-width: 720px) 100vw, 50vw"
          />
          <div className="featured__overlay">
            <h3>{item.categoria.nome}</h3>
            <span className="featured__cta">{t("categorieCtaLabel")}</span>
          </div>
        </Link>
      ))}
    </div>
  );
}
```

- [ ] **Step 5: Add CSS**

Append to `web/src/styles/pages.css`:

```css
/* ---------- In evidenza (categorie con foto) ---------- */
.featured{ display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-5); }
.featured__card{ position:relative; display:block; aspect-ratio:4/3; border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--line); }
.featured__card img{ object-fit:cover; transition:transform .3s ease; }
.featured__card:hover img{ transform:scale(1.04); }
.featured__overlay{ position:absolute; inset:0; background:linear-gradient(180deg, rgba(15,39,64,0) 45%, rgba(15,39,64,.88) 100%); display:flex; flex-direction:column; justify-content:flex-end; padding:var(--sp-5); }
.featured__overlay h3{ color:#fff; font-size:var(--fs-h3); }
.featured__cta{ margin-top:var(--sp-2); display:inline-flex; align-items:center; gap:8px; font-family:var(--f-mono); font-size:var(--fs-xs); color:#fff; text-transform:uppercase; letter-spacing:.06em; }
@media (max-width:720px){ .featured{ grid-template-columns:1fr; } }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `cd web && npx vitest run src/components/ui/FeaturedCategories.test.tsx`
Expected: PASS (2 tests)

- [ ] **Step 7: Commit**

```bash
git add web/src/components/ui/FeaturedCategories.tsx web/src/components/ui/FeaturedCategories.test.tsx web/src/styles/pages.css web/src/i18n/messages/it.json web/src/i18n/messages/en.json
git commit -m "feat: add FeaturedCategories block for the historic 'In evidenza' section"
```

---

### Task 7: `RealizzazioniBand` — dark full-bleed "Realizzazioni" section

Wraps the existing `Rail`/`ProjectCard` (already correct data-wise: `getProgetti({ filters: { inEvidenza: true } })`) in a dark full-bleed band matching the old site's black "Realizzazioni" strip, restyled with the current blueprint palette (`--deep` background, `--accent` heading).

**Files:**
- Create: `web/src/components/ui/RealizzazioniBand.tsx`
- Modify: `web/src/styles/pages.css`
- Test: `web/src/components/ui/RealizzazioniBand.test.tsx` (new)

**Interfaces:**
- Consumes: `ProgettoSummary[]` (existing), `Rail`, `ProjectCard` (existing, unchanged), `Locale`.
- Produces: `RealizzazioniBand({ progetti, locale, title, subtitle, kick, ctaLabel, ctaHref }: {...})`. Renders nothing when `progetti` is empty (mirrors the current home rail's `evidenza.length > 0` guard).

- [ ] **Step 1: Write the failing test**

```tsx
// web/src/components/ui/RealizzazioniBand.test.tsx
import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { RealizzazioniBand } from "./RealizzazioniBand";

const progetto = {
  id: "1",
  slug: "aiassa",
  titolo: "Aiassa",
  cliente: "Aiassa Costruzioni",
  luogo: "Piacenza (PC)",
  anno: 2018,
  categoria: { slug: "strutture-miste" as const, nome: "Strutture miste" },
  inEvidenza: true,
  copertina: { src: "/a.jpg", width: 1200, height: 900, alt: "Aiassa" },
};

describe("RealizzazioniBand", () => {
  it("renders the section with the provided projects", () => {
    render(
      <NextIntlClientProvider locale="it" messages={{ Rail: { prev: "Precedente", next: "Successivo" } }}>
        <RealizzazioniBand
          progetti={[progetto]}
          locale="it"
          kick="In evidenza"
          title="Realizzazioni recenti."
          subtitle="Una selezione di commesse."
          ctaLabel="Tutte le realizzazioni"
          ctaHref="/it/realizzazioni"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByRole("heading", { name: "Realizzazioni recenti." })).toBeInTheDocument();
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
  });

  it("renders nothing when there are no projects", () => {
    const { container } = render(
      <NextIntlClientProvider locale="it" messages={{}}>
        <RealizzazioniBand
          progetti={[]}
          locale="it"
          kick="In evidenza"
          title="Realizzazioni recenti."
          subtitle="Una selezione."
          ctaLabel="Tutte"
          ctaHref="/it/realizzazioni"
        />
      </NextIntlClientProvider>
    );
    expect(container).toBeEmptyDOMElement();
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd web && npx vitest run src/components/ui/RealizzazioniBand.test.tsx`
Expected: FAIL — module doesn't exist.

- [ ] **Step 3: Implement**

```tsx
// web/src/components/ui/RealizzazioniBand.tsx
import Link from "next/link";
import type { ProgettoSummary } from "@/domain";
import type { Locale } from "@/i18n/routing";
import { ProjectCard } from "./ProjectCard";
import { Rail } from "./Rail";
import { Reveal } from "./Reveal";

type RealizzazioniBandProps = {
  progetti: ProgettoSummary[];
  locale: Locale;
  kick: string;
  title: string;
  subtitle: string;
  ctaLabel: string;
  ctaHref: string;
};

const rightArrow = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
    <path d="M5 12h14M13 6l6 6-6 6" />
  </svg>
);

/**
 * Banda scura "Realizzazioni" (blocco storico del sito, restyle blueprint):
 * rail delle realizzazioni in evidenza su sfondo `--deep`.
 */
export function RealizzazioniBand({
  progetti,
  locale,
  kick,
  title,
  subtitle,
  ctaLabel,
  ctaHref,
}: RealizzazioniBandProps) {
  if (progetti.length === 0) {
    return null;
  }

  return (
    <section className="section realiz-band">
      <div className="container">
        <Reveal className="rail-head">
          <div>
            <span className="kicker">
              <span className="num">03</span>
              <span className="txt">{kick}</span>
            </span>
            <h2>{title}</h2>
            <p>{subtitle}</p>
          </div>
          <Link className="link-mono" href={ctaHref}>
            {ctaLabel} {rightArrow}
          </Link>
        </Reveal>
        <Reveal>
          <Rail>
            {progetti.map((p) => (
              <ProjectCard key={p.id} progetto={p} locale={locale} />
            ))}
          </Rail>
        </Reveal>
      </div>
    </section>
  );
}
```

- [ ] **Step 4: Add CSS**

Append to `web/src/styles/pages.css`:

```css
/* ---------- Realizzazioni (banda scura, blocco storico) ---------- */
.realiz-band{ background:var(--deep); }
.realiz-band .rail-head .kicker .txt{ color:rgba(255,255,255,.6); }
.realiz-band .rail-head .kicker .num{ color:var(--accent); }
.realiz-band .rail-head h2{ color:#fff; }
.realiz-band .rail-head p{ color:rgba(255,255,255,.75); }
.realiz-band .link-mono{ color:#fff; }
.realiz-band .proj{ background:rgba(255,255,255,.04); border-color:rgba(255,255,255,.14); }
.realiz-band .proj:hover{ border-color:var(--accent); }
.realiz-band .proj__body .cli{ color:var(--accent); }
.realiz-band .proj__body h3{ color:#fff; }
.realiz-band .proj__body .meta{ color:rgba(255,255,255,.6); }
.realiz-band .rail-arrow{ background:rgba(255,255,255,.06); border-color:rgba(255,255,255,.2); color:#fff; }
.realiz-band .rail-arrow:hover:not(:disabled){ border-color:var(--accent); color:var(--accent); }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd web && npx vitest run src/components/ui/RealizzazioniBand.test.tsx`
Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```bash
git add web/src/components/ui/RealizzazioniBand.tsx web/src/components/ui/RealizzazioniBand.test.tsx web/src/styles/pages.css
git commit -m "feat: add dark RealizzazioniBand section matching the historic site block"
```

---

### Task 8: `ContactInfoBand` — global Contatti/Orari/Telefono band

**Files:**
- Create: `web/src/components/layout/ContactInfoBand.tsx`
- Modify: `web/src/styles/base.css`
- Modify: `web/src/i18n/messages/it.json`, `web/src/i18n/messages/en.json`
- Test: `web/src/components/layout/ContactInfoBand.test.tsx` (new)

**Interfaces:**
- Consumes: `SiteSettings` (Task 1).
- Produces: `ContactInfoBand({ settings }: { settings: SiteSettings })`. Always renders 3 columns (Contatti, Orari ufficio, Telefono) since `indirizzo`/`telefono`/`email` are non-optional on `SiteSettings`; the fax line only renders when `settings.fax` is set.

- [ ] **Step 1: Add i18n namespace**

In both `web/src/i18n/messages/it.json` and `en.json`, add a new top-level namespace (alongside `"Home"`, `"Footer"`, etc.):

```json
"ContactBand": {
  "contattiTitle": "Contatti",
  "orariTitle": "Orari ufficio",
  "telefonoTitle": "Telefono",
  "faxLabel": "Fax",
  "preventiviText": "Per informazioni e preventivi contattateci!"
}
```

English:

```json
"ContactBand": {
  "contattiTitle": "Contact",
  "orariTitle": "Office hours",
  "telefonoTitle": "Phone",
  "faxLabel": "Fax",
  "preventiviText": "For information and quotes, get in touch!"
}
```

- [ ] **Step 2: Write the failing test**

```tsx
// web/src/components/layout/ContactInfoBand.test.tsx
import "@testing-library/jest-dom/vitest";
import { render, screen } from "@testing-library/react";
import { NextIntlClientProvider } from "next-intl";
import { describe, expect, it } from "vitest";
import { ContactInfoBand } from "./ContactInfoBand";
import messages from "@/i18n/messages/it.json";

const settings = {
  nomeAzienda: "Edilmetal",
  ragioneSociale: "Edilmetal S.r.l.",
  partitaIva: "01234567890",
  indirizzo: "Piazza Alpini d'Italia 10/A, 43015 Noceto (PR)",
  telefono: "0521 615023",
  fax: "0521 615207",
  email: "info@edilmetal.it",
  coordinate: { lat: 44.8103, lng: 10.1747 },
  orari: [{ giorni: "Lun–Ven", apertura: "08:00–12:00 / 14:00–18:00" }],
  social: {},
};

describe("ContactInfoBand", () => {
  it("shows address, hours and phone/fax", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ContactInfoBand settings={settings} />
      </NextIntlClientProvider>
    );
    expect(screen.getByText(settings.indirizzo)).toBeInTheDocument();
    expect(screen.getByText("Lun–Ven")).toBeInTheDocument();
    expect(screen.getByText("08:00–12:00 / 14:00–18:00")).toBeInTheDocument();
    expect(screen.getByText(/0521 615023/)).toBeInTheDocument();
    expect(screen.getByText(/0521 615207/)).toBeInTheDocument();
  });

  it("omits the fax line when settings.fax is absent", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <ContactInfoBand settings={{ ...settings, fax: undefined }} />
      </NextIntlClientProvider>
    );
    expect(screen.queryByText(/Fax/)).not.toBeInTheDocument();
  });
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `cd web && npx vitest run src/components/layout/ContactInfoBand.test.tsx`
Expected: FAIL — module doesn't exist.

- [ ] **Step 4: Implement**

```tsx
// web/src/components/layout/ContactInfoBand.tsx
import { useTranslations } from "next-intl";
import type { SiteSettings } from "@/domain";

type ContactInfoBandProps = {
  settings: SiteSettings;
};

const mailIcon = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
    <path d="M4 5h16v14H4z" />
    <path d="m4 6 8 7 8-7" />
  </svg>
);
const clockIcon = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
    <circle cx="12" cy="12" r="9" />
    <path d="M12 7v5l3 2" />
  </svg>
);
const phoneIcon = (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
    <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" />
  </svg>
);

/**
 * Banda globale "Contatti / Orari ufficio / Telefono" (blocco presente su
 * ogni pagina nel sito storico). Renderizzata nel layout, sopra il footer.
 */
export function ContactInfoBand({ settings }: ContactInfoBandProps) {
  const t = useTranslations("ContactBand");
  const phoneHref = `tel:${settings.telefono.replace(/\s/g, "")}`;

  return (
    <section className="contact-band">
      <div className="container contact-band__grid">
        <div className="contact-band__item">
          <span className="ico">{mailIcon}</span>
          <h3>{t("contattiTitle")}</h3>
          <p>{settings.indirizzo}</p>
          <p>
            <a href={`mailto:${settings.email}`}>{settings.email}</a>
          </p>
        </div>

        <div className="contact-band__item">
          <span className="ico">{clockIcon}</span>
          <h3>{t("orariTitle")}</h3>
          {settings.orari.map((o) => (
            <p key={o.giorni}>
              {o.giorni}
              <br />
              {o.apertura}
            </p>
          ))}
        </div>

        <div className="contact-band__item">
          <span className="ico">{phoneIcon}</span>
          <h3>{t("telefonoTitle")}</h3>
          <p>{t("preventiviText")}</p>
          <p>
            <a href={phoneHref}>{settings.telefono}</a>
            {settings.fax ? (
              <>
                {" · "}
                {t("faxLabel")}: {settings.fax}
              </>
            ) : null}
          </p>
        </div>
      </div>
    </section>
  );
}
```

- [ ] **Step 5: Add CSS**

Append to `web/src/styles/base.css`:

```css
/* ---------- Banda globale Contatti/Orari/Telefono ---------- */
.contact-band{ background:var(--bg-2); border-top:1px solid var(--line); border-bottom:1px solid var(--line); padding-block:clamp(40px,6vw,64px); }
.contact-band__grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:var(--sp-6); text-align:center; }
.contact-band__item .ico{ display:inline-flex; width:36px; height:36px; color:var(--accent-ui); }
.contact-band__item h3{ font-family:var(--f-mono); font-size:var(--fs-sm); text-transform:uppercase; letter-spacing:.08em; color:var(--ink); margin-top:var(--sp-3); }
.contact-band__item p{ color:var(--ink-2); margin-top:var(--sp-2); font-size:var(--fs-sm); }
.contact-band__item a{ color:var(--ink-2); }
.contact-band__item a:hover{ color:var(--accent-ui); }
@media (max-width:720px){ .contact-band__grid{ grid-template-columns:1fr; gap:var(--sp-5); } }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `cd web && npx vitest run src/components/layout/ContactInfoBand.test.tsx`
Expected: PASS (2 tests)

- [ ] **Step 7: Commit**

```bash
git add web/src/components/layout/ContactInfoBand.tsx web/src/components/layout/ContactInfoBand.test.tsx web/src/styles/base.css web/src/i18n/messages/it.json web/src/i18n/messages/en.json
git commit -m "feat: add global ContactInfoBand (Contatti/Orari/Telefono) shown on every page"
```

---

### Task 9: Wire `ContactInfoBand` into the locale layout

**Files:**
- Modify: `web/src/app/[locale]/layout.tsx`

**Interfaces:**
- Consumes: `ContactInfoBand` (Task 8), existing `settings` already fetched in the layout.

- [ ] **Step 1: Import and render**

In `web/src/app/[locale]/layout.tsx`, add the import:

```ts
import { ContactInfoBand } from "@/components/layout/ContactInfoBand";
```

and change:

```tsx
          <Header locale={locale} settings={settings} />
          <main id="main">{children}</main>
          <Footer locale={locale} settings={settings} />
```

to:

```tsx
          <Header locale={locale} settings={settings} />
          <main id="main">{children}</main>
          <ContactInfoBand settings={settings} />
          <Footer locale={locale} settings={settings} />
```

- [ ] **Step 2: Run typecheck**

Run: `cd web && npm run typecheck`
Expected: no errors from `layout.tsx`.

- [ ] **Step 3: Commit**

```bash
git add web/src/app/[locale]/layout.tsx
git commit -m "feat: render ContactInfoBand on every page, between content and footer"
```

---

### Task 10: Rewrite the Home page to the historic block sequence

**Files:**
- Modify: `web/src/app/[locale]/page.tsx`

**Interfaces:**
- Consumes: `Hero` (Task 5), `FeaturedCategories` (Task 6), `RealizzazioniBand` (Task 7), `getPage`/`getProgetti`/`getSettings` (existing, unchanged signatures).

- [ ] **Step 1: Rewrite the component body**

Replace the whole file content (imports + component) with:

```tsx
import type { Metadata } from "next";
import { getTranslations, setRequestLocale } from "next-intl/server";
import { routing, type Locale } from "@/i18n/routing";
import { getPage, getProgetti, getSettings } from "@/lib/api";
import {
  JsonLd,
  absoluteUrl,
  buildMetadata,
  buildOrganizationJsonLd,
  localePath,
  localePathsFor,
} from "@/lib/seo";
import { Hero } from "@/components/ui/Hero";
import { FeaturedCategories } from "@/components/ui/FeaturedCategories";
import { RealizzazioniBand } from "@/components/ui/RealizzazioniBand";

type PageProps = { params: Promise<{ locale: string }> };

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale: locale as Locale, namespace: "SEO" });
  return buildMetadata({
    locale: locale as Locale,
    paths: localePathsFor(),
    siteName: t("siteName"),
    heading: t("homeHeading"),
    description: t("homeDescription"),
  });
}

export default async function HomePage({ params }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const [page, evidenza, settings] = await Promise.all([
    getPage({ locale, key: "home" }),
    getProgetti({ locale, filters: { inEvidenza: true } }),
    getSettings({ locale }),
  ]);

  const t = await getTranslations("Home");
  const home = page?.home;

  const organizationJsonLd = buildOrganizationJsonLd({
    settings,
    url: absoluteUrl(localePath(locale)),
    logoUrl: absoluteUrl("/logo-edilmetal.png"),
  });

  if (!home) {
    return <JsonLd data={organizationJsonLd} />;
  }

  return (
    <>
      <JsonLd data={organizationJsonLd} />

      <Hero
        hero={home.hero}
        locale={locale}
        heroImage={settings.heroImage}
        fotoCredit={settings.fotoCredit}
      />

      {/* In evidenza — 2 categorie con foto reale (blocco storico) */}
      <section className="section">
        <div className="container">
          <span className="sec-label">
            <span className="num">01</span>
            <span className="kick">{t("categorieKick")}</span>
            <span className="bar" />
          </span>
          <div className="mt-6">
            <FeaturedCategories items={home.inEvidenza} locale={locale} />
          </div>
        </div>
      </section>

      {/* Realizzazioni — banda scura con foto reali (blocco storico) */}
      <RealizzazioniBand
        progetti={evidenza}
        locale={locale}
        kick={t("evidenzaKick")}
        title={t("evidenzaTitle")}
        subtitle={t("evidenzaSub")}
        ctaLabel={t("tutteLeRealizzazioni")}
        ctaHref={`/${locale}/realizzazioni`}
      />
    </>
  );
}
```

Note: `ContactInfoBand`/`Footer` no longer need explicit rendering here — they come from the layout (Tasks 8–9) on every page, matching the historic site's behaviour.

- [ ] **Step 2: Run typecheck, lint, unit tests**

Run: `cd web && npm run typecheck && npm run lint && npm run test`
Expected: all green. If `typecheck` still lists errors, they must be exactly the ones from Task 2 Step 5 that this task was supposed to close — investigate and fix any stragglers (e.g. leftover imports of `StatsRow`/`Flow`/`Feats`/`Clients`/`CtaBand`/`SectionLabel`/`Rail`/`ProjectCard`/`Reveal` in this file that are no longer used).

- [ ] **Step 3: Commit**

```bash
git add web/src/app/[locale]/page.tsx
git commit -m "feat(home): rebuild page around Hero → In evidenza → Realizzazioni, drop unwired sections"
```

---

### Task 11: `/realizzazioni` — honour `?categoria=` from the "In evidenza" links

**Files:**
- Modify: `web/src/app/[locale]/realizzazioni/page.tsx`
- Modify: `web/src/components/ui/RealizzazioniView.tsx`
- Test: `web/src/components/ui/RealizzazioniView.test.tsx` (new)

**Interfaces:**
- Consumes: `categoriaSlugSchema` (existing, `@/domain`).
- Produces: `RealizzazioniView` gains an optional `initialCategoria?: CategoriaSlug` prop, used only as the initial value of the existing `cat` state (no other behavioural change).

- [ ] **Step 1: Write the failing test**

```tsx
// web/src/components/ui/RealizzazioniView.test.tsx
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

describe("RealizzazioniView", () => {
  it("pre-filters by the given initialCategoria", () => {
    render(
      <NextIntlClientProvider locale="it" messages={messages}>
        <RealizzazioniView
          summaries={summaries}
          categorie={[
            { slug: "strutture-miste", nome: "Strutture miste" },
            { slug: "strutture-acciaio", nome: "Strutture in acciaio" },
          ]}
          settori={[]}
          anni={[2018]}
          locale="it"
          initialCategoria="strutture-miste"
        />
      </NextIntlClientProvider>
    );
    expect(screen.getByText("Aiassa")).toBeInTheDocument();
    expect(screen.queryByText("Acetum")).not.toBeInTheDocument();
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd web && npx vitest run src/components/ui/RealizzazioniView.test.tsx`
Expected: FAIL — `initialCategoria` prop doesn't exist, both projects render.

- [ ] **Step 3: Implement — `RealizzazioniView.tsx`**

Change:

```ts
type RealizzazioniViewProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  settori: string[];
  anni: number[];
  locale: Locale;
};
```

to:

```ts
type RealizzazioniViewProps = {
  summaries: ProgettoSummary[];
  categorie: Categoria[];
  settori: string[];
  anni: number[];
  locale: Locale;
  /** Categoria pre-selezionata (es. da un link "vai alla sezione prodotti"). */
  initialCategoria?: CategoriaSlug;
};
```

and change:

```ts
export function RealizzazioniView({
  summaries,
  categorie,
  settori,
  anni,
  locale,
}: RealizzazioniViewProps) {
  const t = useTranslations("Realizzazioni");
  const [cat, setCat] = useState<CategoriaSlug | null>(null);
```

to:

```ts
export function RealizzazioniView({
  summaries,
  categorie,
  settori,
  anni,
  locale,
  initialCategoria,
}: RealizzazioniViewProps) {
  const t = useTranslations("Realizzazioni");
  const [cat, setCat] = useState<CategoriaSlug | null>(initialCategoria ?? null);
```

- [ ] **Step 4: Implement — `realizzazioni/page.tsx`**

Change the `PageProps` type and function signature:

```ts
type PageProps = {
  params: Promise<{ locale: string }>;
  searchParams: Promise<{ categoria?: string }>;
};
```

```tsx
export default async function RealizzazioniPage({ params, searchParams }: PageProps) {
  const { locale: rawLocale } = await params;
  const locale = rawLocale as Locale;
  setRequestLocale(locale);

  const { categoria } = await searchParams;
  const parsedCategoria = categoriaSlugSchema.safeParse(categoria);
  const initialCategoria = parsedCategoria.success ? parsedCategoria.data : undefined;

  const summaries = await getProgetti({ locale });
```

and add the import at the top:

```ts
import { categoriaSlugSchema } from "@/domain";
```

Finally, pass the new prop where `<RealizzazioniView .../>` is rendered:

```tsx
            <RealizzazioniView
              summaries={summaries}
              categorie={categorie}
              settori={settori}
              anni={anni}
              locale={locale}
              initialCategoria={initialCategoria}
            />
```

- [ ] **Step 5: Run test to verify it passes**

Run: `cd web && npx vitest run src/components/ui/RealizzazioniView.test.tsx`
Expected: PASS

- [ ] **Step 6: Run full frontend verification**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: all green (the `build` step exercises static export generation and will surface anything `getStaticParams`/searchParams-related).

- [ ] **Step 7: Commit**

```bash
git add web/src/app/[locale]/realizzazioni/page.tsx web/src/components/ui/RealizzazioniView.tsx web/src/components/ui/RealizzazioniView.test.tsx
git commit -m "feat(realizzazioni): honour ?categoria= so 'In evidenza' CTAs land pre-filtered"
```

---

## Part C — WordPress: fields, presenters, contract docs

### Task 12: `SettingsFields.php` + `SettingsPresenter.php` — add `fax`

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/fields/SettingsFields.php`
- Modify: `cms/mu-plugins/edilmetal-core/rest/Presenters/SettingsPresenter.php`

**Interfaces:**
- Produces: DTO key `fax` (string, only present when non-empty), consumed by `SiteSettings.fax` (Task 1).

- [ ] **Step 1: Add the field**

In `SettingsFields.php`, in the `'Contatti'` group (right after the `$this->text( 'edilmetal_set_telefono', ... )` line), add:

```php
					$this->text( 'edilmetal_set_fax', __( 'Fax', 'edilmetal-core' ) ),
```

- [ ] **Step 2: Map it in the presenter**

In `SettingsPresenter.php`, right after the block:

```php
		$foto_credit = $meta->string( 'edilmetal_set_foto_credit' );
		if ( '' !== $foto_credit ) {
			$dto['fotoCredit'] = $foto_credit;
		}
```

add:

```php
		$fax = $meta->string( 'edilmetal_set_fax' );
		if ( '' !== $fax ) {
			$dto['fax'] = $fax;
		}
```

- [ ] **Step 3: PHP lint**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/fields/SettingsFields.php mu-plugins/edilmetal-core/rest/Presenters/SettingsPresenter.php`
Expected: no new violations (fix any reported by `composer phpcbf` if formatting drifts).

- [ ] **Step 4: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/fields/SettingsFields.php cms/mu-plugins/edilmetal-core/rest/Presenters/SettingsPresenter.php
git commit -m "feat(wp): expose optional fax in site settings"
```

---

### Task 13: `PageFields.php` — rebuild the `home_box()` field set

Removes the fields that were never read by the presenter or are now redundant (`edilmetal_home_hero_img` — superseded by the global `edilmetal_set_hero_image` from Task 12/Settings; `edilmetal_home_stats`, `edilmetal_home_intro_*` — dead), and adds the fields the new contract needs: hero secondary CTA, hero index bar (3 stat pairs), and the 2 "in evidenza" category slots.

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/fields/PageFields.php`

**Interfaces:**
- Produces meta keys consumed by Task 14: `edilmetal_home_hero_cta2_label`, `edilmetal_home_hero_cta2_url`, `edilmetal_home_hero_index` (clonable "valore|etichetta"), `edilmetal_home_evidenza1_categoria` / `edilmetal_home_evidenza1_img` / `edilmetal_home_evidenza2_categoria` / `edilmetal_home_evidenza2_img`.

- [ ] **Step 1: Rewrite `home_box()`**

Replace the whole method body with:

```php
	private function home_box(): array {
		$fields = array(
			$this->text( 'edilmetal_home_hero_eyebrow', __( 'Hero — Eyebrow', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_titolo', __( 'Hero — Titolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_titolo_accent', __( 'Hero — Titolo (accento)', 'edilmetal-core' ) ),
			$this->textarea( 'edilmetal_home_hero_sottotitolo', __( 'Hero — Sottotitolo', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_cta_label', __( 'Hero — CTA primaria (testo)', 'edilmetal-core' ) ),
			$this->url( 'edilmetal_home_hero_cta_url', __( 'Hero — CTA primaria (URL)', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_hero_cta2_label', __( 'Hero — CTA secondaria (testo, opzionale)', 'edilmetal-core' ) ),
			$this->url( 'edilmetal_home_hero_cta2_url', __( 'Hero — CTA secondaria (URL, opzionale)', 'edilmetal-core' ) ),
			$this->repeater_text( 'edilmetal_home_hero_index', __( 'Hero — Barra indice, max 3 voci (formato "valore|etichetta")', 'edilmetal-core' ) ),

			$this->text( 'edilmetal_home_evidenza1_categoria', __( 'In evidenza 1 — Slug categoria', 'edilmetal-core' ) ),
			$this->image( 'edilmetal_home_evidenza1_img', __( 'In evidenza 1 — Immagine', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_home_evidenza2_categoria', __( 'In evidenza 2 — Slug categoria', 'edilmetal-core' ) ),
			$this->image( 'edilmetal_home_evidenza2_img', __( 'In evidenza 2 — Immagine', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_home' ) );

		return $this->box( 'edilmetal_page_home', __( 'Contenuti: Homepage', 'edilmetal-core' ), $fields );
	}
```

Add a `'desc'` hint to the two `edilmetal_home_evidenzaN_categoria` fields so editors know the valid values — extend the `text()` calls above to pass a description. Since the existing `text()` helper (`PageFields.php:81-87`) doesn't support `desc`, add it inline instead of through the helper:

```php
			array(
				'id'   => 'edilmetal_home_evidenza1_categoria',
				'name' => __( 'In evidenza 1 — Slug categoria', 'edilmetal-core' ),
				'type' => 'text',
				'desc' => __( 'Uno tra: strutture-acciaio, strutture-miste, scale, pensiline, pensiline-auto, coperture-tamponamenti, rivestimenti-facciata, opere-speciali', 'edilmetal-core' ),
			),
			$this->image( 'edilmetal_home_evidenza1_img', __( 'In evidenza 1 — Immagine', 'edilmetal-core' ) ),
			array(
				'id'   => 'edilmetal_home_evidenza2_categoria',
				'name' => __( 'In evidenza 2 — Slug categoria', 'edilmetal-core' ),
				'type' => 'text',
				'desc' => __( 'Uno tra: strutture-acciaio, strutture-miste, scale, pensiline, pensiline-auto, coperture-tamponamenti, rivestimenti-facciata, opere-speciali', 'edilmetal-core' ),
			),
			$this->image( 'edilmetal_home_evidenza2_img', __( 'In evidenza 2 — Immagine', 'edilmetal-core' ) ),
```

(use this version — with the two `array(...)` blocks in place of the two plain `$this->text('edilmetal_home_evidenzaN_categoria', ...)` calls shown first — in the final file).

- [ ] **Step 2: PHP lint**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/fields/PageFields.php`
Expected: clean (run `composer phpcbf` if not).

- [ ] **Step 3: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/fields/PageFields.php
git commit -m "feat(wp): rebuild home Meta Box fields for hero index/cta2 and 2 featured categories"
```

---

### Task 14: `PagePresenter.php` — rewrite the `home` DTO

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php`

**Interfaces:**
- Consumes: fields from Task 13, `Schema::TAX_CATEGORIA` (`cms/mu-plugins/edilmetal-core/src/Support/Schema.php`), `ImageTransformer::to_front()` (existing).
- Produces: `home.hero = { eyebrow?, title, titleAccent?, subtitle, ctaPrimary: {label, href}, ctaSecondary?: {label, href}, index: [{valore, etichetta}] }`, `home.inEvidenza = [{ categoria: {slug, nome}, immagine: Image }]` (0–2 entries). Matches `homeContentSchema` from Task 2 exactly.

- [ ] **Step 1: Add the `Schema` import**

At the top of `PagePresenter.php`, add:

```php
use Edilmetal\Core\Support\Schema;
```

- [ ] **Step 2: Update the `home` case**

Replace:

```php
			case 'home':
				$dto['hero']  = $this->home_hero();
				$dto['stats'] = $this->stats( 'edilmetal_home_stats' );
				$this->maybe_block( $dto, 'intro', $this->intro( 'edilmetal_home_intro' ) );
				break;
```

with:

```php
			case 'home':
				$dto['hero']       = $this->home_hero();
				$dto['inEvidenza'] = $this->home_in_evidenza();
				break;
```

- [ ] **Step 3: Rewrite `home_hero()`**

Replace the whole method with:

```php
	/**
	 * Blocco hero della home { eyebrow?, title, titleAccent?, subtitle, ctaPrimary, ctaSecondary?, index }.
	 *
	 * @return array<string,mixed>
	 */
	private function home_hero(): array {
		$hero = array(
			'title'      => $this->meta->string( 'edilmetal_home_hero_titolo' ),
			'subtitle'   => $this->meta->string( 'edilmetal_home_hero_sottotitolo' ),
			'ctaPrimary' => $this->cta_href( 'edilmetal_home_hero_cta' )
				?? array( 'label' => 'Le realizzazioni', 'href' => '/realizzazioni' ),
			'index'      => $this->pairs( 'edilmetal_home_hero_index', 'valore', 'etichetta' ),
		);

		$this->maybe( $hero, 'eyebrow', $this->meta->string( 'edilmetal_home_hero_eyebrow' ) );
		$this->maybe( $hero, 'titleAccent', $this->meta->string( 'edilmetal_home_hero_titolo_accent' ) );

		$secondary = $this->cta_href( 'edilmetal_home_hero_cta2' );
		if ( null !== $secondary ) {
			$hero['ctaSecondary'] = $secondary;
		}

		return $hero;
	}

	/**
	 * Variante di `cta()` con chiave `href` (contratto frontend) al posto di `url`.
	 *
	 * @param string $prefix Prefisso dei meta ("{prefix}_label" / "{prefix}_url").
	 * @return array{label:string,href:string}|null
	 */
	private function cta_href( string $prefix ): ?array {
		$cta = $this->cta( $prefix );

		if ( array() === $cta ) {
			return null;
		}

		return array(
			'label' => $cta['label'],
			'href'  => $cta['url'],
		);
	}

	/**
	 * Blocco "in evidenza": fino a 2 categorie con foto reale.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function home_in_evidenza(): array {
		$items = array();

		foreach ( array( 1, 2 ) as $n ) {
			$item = $this->evidenza_item( $n );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Singola voce "in evidenza": risolve slug categoria + immagine.
	 *
	 * @param int $n Indice dello slot (1 o 2).
	 * @return array<string,mixed>|null
	 */
	private function evidenza_item( int $n ): ?array {
		$slug          = $this->meta->string( "edilmetal_home_evidenza{$n}_categoria" );
		$attachment_id = $this->meta->int_or_null( "edilmetal_home_evidenza{$n}_img" );

		if ( '' === $slug || null === $attachment_id ) {
			return null;
		}

		$term = get_term_by( 'slug', $slug, Schema::TAX_CATEGORIA );

		if ( ! $term instanceof \WP_Term ) {
			return null;
		}

		$image = $this->images->to_front( $attachment_id );

		if ( null === $image ) {
			return null;
		}

		return array(
			'categoria' => array(
				'slug' => $term->slug,
				'nome' => $term->name,
			),
			'immagine'  => $image,
		);
	}
```

Note for the implementer: `cta()` (existing private method, unchanged) already returns `array()` when either `_label` or `_url` is empty — `cta_href()` just remaps its `url` key to `href` to match the frontend `ctaSchema`. This also fixes a pre-existing bug: the old `home_hero()` built `$hero['cta']` with a `url` key that the frontend's `ctaSchema` (`{label, href}`) would have silently dropped.

- [ ] **Step 4: PHP lint**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php`
Expected: clean (run `composer phpcbf` if not).

- [ ] **Step 5: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php
git commit -m "fix(wp): rebuild home DTO to match hero.ctaPrimary/href contract + add inEvidenza"
```

---

### Task 15: `docs/api-contract.md` — update the Home / Settings sections

**Files:**
- Modify: `docs/api-contract.md`

- [ ] **Step 1: Find and update**

Run: `grep -n "home\|SiteSettings\|heroImage" docs/api-contract.md` to locate the current Home and SiteSettings contract sections, then edit them to match the new shapes exactly:

```
HomeContent {
  hero: {
    eyebrow?: string
    title: string
    titleAccent?: string
    subtitle: string
    ctaPrimary: { label: string; href: string }
    ctaSecondary?: { label: string; href: string }
    index: Array<{ valore: string; etichetta: string }>   // max 3
  }
  inEvidenza: Array<{
    categoria: { slug: CategoriaSlug; nome: string }
    immagine: Image
  }>   // max 2
}

SiteSettings {
  ...campi esistenti...
  fax?: string
  heroImage?: Image
  fotoCredit?: string
}
```

Remove any documented `stats`/`statsIntro`/`processo`/`perche`/`referenze`/`cta` under Home if present.

- [ ] **Step 2: Commit**

```bash
git add docs/api-contract.md
git commit -m "docs: update Home/SiteSettings contract for the historic-structure restyle"
```

---

## Part D — Real content: photos → media library → progetti

### Task 16: `ProgettoSeeder`/`Catalog::progetto()` — support a per-project gallery

Today `ProgettoSeeder::write_gallery()` (`cms/mu-plugins/edilmetal-core/seed/ProgettoSeeder.php:216-233`) always writes the same 3 generic placeholder keys (`self::GALLERY_KEYS`) regardless of the record. This task makes the gallery configurable per record while keeping every existing curated project working unchanged (new parameter is optional, defaults preserve current behaviour).

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/seed/ProgettoSeeder.php`
- Modify: `cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php`

**Interfaces:**
- Produces: `Catalog::progetto(..., array $media = array())` — new optional trailing parameter, an array of `MediaLibrary` keys (in gallery order). `record['media']` read by `ProgettoSeeder::write_gallery()`.

- [ ] **Step 1: Extend `Catalog::progetto()`**

In `Catalog.php`, change the signature (currently ending `array $lavorazioni, array $materiali`) to:

```php
	private static function progetto(
		string $ref,
		string $title,
		string $categoria,
		string $settore,
		string $cliente,
		string $luogo,
		int $anno,
		bool $in_evidenza,
		string $tipologia,
		?float $superficie_mq,
		?float $luce_m,
		?float $altezza_m,
		?float $peso_t,
		array $lavorazioni,
		array $materiali,
		array $media = array()
	): array {
```

and add `'media' => $media,` to the returned array (any position, e.g. right after `'materiali' => $materiali,`). All 12 existing call sites keep working unchanged (they simply don't pass the new trailing arg, so `$media` defaults to `array()`).

- [ ] **Step 2: Update `ProgettoSeeder::write_gallery()`**

Change the method signature and body from:

```php
	private function write_gallery( int $post_id, MediaLibrary $media_library ): void {
		$key = Schema::meta( 'galleria' );
		delete_post_meta( $post_id, $key );

		$attachments = $media_library->gallery( self::GALLERY_KEYS );
```

to:

```php
	private function write_gallery( int $post_id, array $record, MediaLibrary $media_library ): void {
		$key = Schema::meta( 'galleria' );
		delete_post_meta( $post_id, $key );

		$media_keys  = ! empty( $record['media'] ) ? $record['media'] : self::GALLERY_KEYS;
		$attachments = $media_library->gallery( $media_keys );
```

(the rest of the method body is unchanged) and update its call site in `upsert()`:

```php
		$this->write_gallery( $post_id, $media_library );
```

→

```php
		$this->write_gallery( $post_id, $record, $media_library );
```

- [ ] **Step 3: PHP lint**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/seed/ProgettoSeeder.php mu-plugins/edilmetal-core/seed/Data/Catalog.php`
Expected: clean.

- [ ] **Step 4: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/seed/ProgettoSeeder.php cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php
git commit -m "feat(wp): allow each seeded progetto to declare its own gallery media keys"
```

---

### Task 17: `RealPhotos` — import the historic photo library

Mirrors `Placeholders` (`cms/mu-plugins/edilmetal-core/seed/Support/Placeholders.php`) but instead of generating GD placeholders, it scans a directory of **already-committed real photos** and returns their absolute paths keyed by a stable logical key. `MediaSeeder` merges these into the same `MediaLibrary` used everywhere else (no change needed to `MediaLibrary` itself — its `gallery(array $keys)`/`placeholder(string $key)` are already generic over the key namespace).

**Files:**
- Create: `cms/mu-plugins/edilmetal-core/seed/Support/RealPhotos.php`
- Modify: `cms/mu-plugins/edilmetal-core/seed/MediaSeeder.php`
- Modify: `cms/mu-plugins/edilmetal-core/seed/SeedModule.php`

**Interfaces:**
- Consumes: files under `cms/seed/media/realizzazioni/<categoria-slug>/<progetto-slug>/NN.jpg` (created in Task 18).
- Produces: `RealPhotos::ensure(): array<string,string>` mapping key `"real:<categoria-slug>/<progetto-slug>/NN"` → absolute file path. `MediaSeeder::seed()` merges these into the returned `MediaLibrary` alongside the 3 generic placeholders (same map, same `MediaLibrary` constructor — no signature change there).

- [ ] **Step 1: Create `RealPhotos.php`**

```php
<?php
/**
 * Elenca le foto storiche reali già presenti in `cms/seed/media/realizzazioni/`
 * (organizzate per categoria/progetto), pronte per l'import in libreria media.
 *
 * A differenza di `Placeholders`, questa classe non genera nulla: i file sono
 * commitati nel repository come asset reali (foto di cantiere 1997–2018).
 *
 * @package Edilmetal\Core
 */

declare( strict_types=1 );

namespace Edilmetal\Core\Seed\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Scansiona `cms/seed/media/realizzazioni/**\/*.jpg` e ne mappa la chiave logica.
 */
final class RealPhotos {

	/**
	 * Directory radice delle foto storiche reali.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Risolve la directory radice (montaggio cms/seed/media o fallback locale).
	 */
	public function __construct() {
		$base            = defined( 'EDILMETAL_SEED_MEDIA_DIR' ) ? (string) EDILMETAL_SEED_MEDIA_DIR : '/cms/seed/media';
		$this->directory = trailingslashit( $base ) . 'realizzazioni';
	}

	/**
	 * Restituisce la mappa chiave logica => percorso assoluto file.
	 *
	 * Chiave: "real:<categoria-slug>/<progetto-slug>/<NN>" (senza estensione).
	 *
	 * @return array<string,string>
	 */
	public function ensure(): array {
		if ( ! is_dir( $this->directory ) ) {
			return array();
		}

		$paths = array();

		foreach ( glob( trailingslashit( $this->directory ) . '*', GLOB_ONLYDIR ) ?: array() as $categoria_dir ) {
			$categoria = basename( $categoria_dir );

			foreach ( glob( trailingslashit( $categoria_dir ) . '*', GLOB_ONLYDIR ) ?: array() as $progetto_dir ) {
				$progetto = basename( $progetto_dir );

				foreach ( glob( trailingslashit( $progetto_dir ) . '*.jpg' ) ?: array() as $file ) {
					$stem        = pathinfo( $file, PATHINFO_FILENAME );
					$key         = "real:{$categoria}/{$progetto}/{$stem}";
					$paths[ $key ] = $file;
				}
			}
		}

		return $paths;
	}
}
```

- [ ] **Step 2: Merge into `MediaSeeder`**

In `MediaSeeder.php`, add the constructor dependency and merge step. Change:

```php
final class MediaSeeder {

	private Placeholders $placeholders;
	private ImageTransformer $images;

	public function __construct( Placeholders $placeholders, ImageTransformer $images ) {
		$this->placeholders = $placeholders;
		$this->images       = $images;
	}

	public function seed(): MediaLibrary {
		$this->load_dependencies();

		return new MediaLibrary( $this->seed_placeholders() );
	}
```

to:

```php
final class MediaSeeder {

	private Placeholders $placeholders;
	private RealPhotos $real_photos;
	private ImageTransformer $images;

	public function __construct( Placeholders $placeholders, RealPhotos $real_photos, ImageTransformer $images ) {
		$this->placeholders = $placeholders;
		$this->real_photos  = $real_photos;
		$this->images       = $images;
	}

	public function seed(): MediaLibrary {
		$this->load_dependencies();

		$library = array_merge( $this->seed_placeholders(), $this->seed_real_photos() );

		return new MediaLibrary( $library );
	}

	/**
	 * Importa le foto storiche reali e ne mappa gli ID.
	 *
	 * @return array<string,int>
	 */
	private function seed_real_photos(): array {
		$library = array();

		foreach ( $this->real_photos->ensure() as $key => $path ) {
			$id = $this->ensure_attachment( 'media:' . $key, basename( $path ), $this->real_photo_alt( $key ), $path );

			if ( null !== $id ) {
				$library[ $key ] = $id;
			}
		}

		return $library;
	}

	/**
	 * Alt testuale derivato dalla chiave "real:<categoria>/<progetto>/<NN>".
	 *
	 * @param string $key Chiave logica della foto.
	 */
	private function real_photo_alt( string $key ): string {
		$parts    = explode( '/', substr( $key, strlen( 'real:' ) ) );
		$progetto = $parts[1] ?? 'realizzazione';
		$label    = str_replace( '-', ' ', $progetto );

		return sprintf( __( 'Edilmetal — %s (foto di cantiere)', 'edilmetal-core' ), ucwords( $label ) );
	}
```

(`ensure_attachment()`/`create_attachment()` stay untouched — they already accept any `$ref`/`$title`/`$alt`/`$path`.)

- [ ] **Step 3: Update `SeedModule.php` wiring**

Change:

```php
use Edilmetal\Core\Seed\Support\Placeholders;
...
			new MediaSeeder( new Placeholders(), new ImageTransformer() ),
```

to:

```php
use Edilmetal\Core\Seed\Support\Placeholders;
use Edilmetal\Core\Seed\Support\RealPhotos;
...
			new MediaSeeder( new Placeholders(), new RealPhotos(), new ImageTransformer() ),
```

- [ ] **Step 4: PHP lint**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/seed/Support/RealPhotos.php mu-plugins/edilmetal-core/seed/MediaSeeder.php mu-plugins/edilmetal-core/seed/SeedModule.php`
Expected: clean.

- [ ] **Step 5: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/seed/Support/RealPhotos.php cms/mu-plugins/edilmetal-core/seed/MediaSeeder.php cms/mu-plugins/edilmetal-core/seed/SeedModule.php
git commit -m "feat(wp): import real historic photos into the seeded media library"
```

(No test file for `RealPhotos`/`MediaSeeder` — this codebase has no PHP unit test harness yet; verification happens end-to-end in Task 21 via `wp edilmetal seed` + the REST endpoints, per existing project convention.)

---

### Task 18: Build the real-photo manifest and copy the files

This is the one-off data-preparation step: group the ~184 photos in `sito web edilmetal 2018/` by client, copy them into the committed `cms/seed/media/realizzazioni/` tree, and emit the JSON manifest `Catalog.php` reads in Task 19.

**Files:**
- Create: `scripts/seed/build-realizzazioni-media.mjs`
- Create (generated by the script, then committed): `cms/seed/media/realizzazioni/**/*.jpg` (~84 folders)
- Create (generated by the script, then committed): `cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json`

**Interfaces:**
- Produces: JSON array of `{ ref: string; titolo: string; categoria: CategoriaSlug; media: string[] }`, where `media` entries are `MediaLibrary` keys of the form `real:<categoria-slug>/<ref>/<NN>` matching the files copied to `cms/seed/media/realizzazioni/<categoria-slug>/<ref>/<NN>.jpg`. Read by `Catalog::historic_progetti()` in Task 19.

- [ ] **Step 1: Write the script**

```js
// scripts/seed/build-realizzazioni-media.mjs
// One-off data-prep script: groups the historic photo dump into per-client
// project folders, copies the files into the committed seed-media tree, and
// emits the manifest consumed by Catalog::historic_progetti().
import { readdirSync, mkdirSync, copyFileSync, writeFileSync, statSync } from "node:fs";
import { join, extname, basename } from "node:path";

const SOURCE_ROOT = "sito web edilmetal 2018";
const MEDIA_ROOT = "cms/seed/media/realizzazioni";
const MANIFEST_PATH = "cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json";

/** Cartella sorgente => slug categoria_opera. */
const CATEGORY_FOLDERS = {
  "1 strutture acciaio": "strutture-acciaio",
  "2 strutture miste": "strutture-miste",
  "3 scale": "scale",
  "4 pensiline": "pensiline",
  "5 pensiline auto": "pensiline-auto",
  "6 coperture e tamponamenti": "coperture-tamponamenti",
  "7 rivestimenti facciata": "rivestimenti-facciata",
  "8 opere speciali": "opere-speciali",
};

/** Nomi-base da scartare: privi di cliente riconoscibile. */
const EXCLUDE_BASENAMES = new Set(["p17-10-08_11."]);

/** Correzioni manuali: nome-base grezzo (minuscolo) => nome-base corretto. */
const BASENAME_OVERRIDES = new Map([
  ["ricci casa-apr07 002-", "ricci casa-apr07"],
]);

/** Acronimi da mantenere maiuscoli nel titolo (dopo Title Case). */
const ACRONYMS = new Map([
  ["Cna", "CNA"],
  ["Sit", "SIT"],
  ["Fbp", "FBP"],
  ["Zrh", "ZRH"],
  ["Ct", "CT"],
  ["Cls", "CLS"],
]);

function titleCase(raw) {
  const words = raw
    .trim()
    .split(/\s+/)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase());
  return words.map((w) => ACRONYMS.get(w) ?? w).join(" ");
}

function slugify(raw) {
  return raw
    .toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function groupPhotos(folderPath) {
  const groups = new Map(); // basename (lowercase, corrected) -> file[]
  for (const file of readdirSync(folderPath).sort()) {
    if (!/\.(jpe?g)$/i.test(file)) continue;
    if (!statSync(join(folderPath, file)).isFile()) continue;

    const stem = file.replace(/\.(jpe?g)$/i, "");
    let base = stem.replace(/\s*\d+\s*$/, "").trim().replace(/\s+/g, " ");
    const key = base.toLowerCase();

    if (EXCLUDE_BASENAMES.has(key)) continue;

    const corrected = BASENAME_OVERRIDES.get(key) ?? base;
    const groupKey = corrected.toLowerCase();

    if (!groups.has(groupKey)) groups.set(groupKey, { title: corrected, files: [] });
    groups.get(groupKey).files.push(file);
  }
  return groups;
}

const manifest = [];

for (const [folderName, categoriaSlug] of Object.entries(CATEGORY_FOLDERS)) {
  const folderPath = join(SOURCE_ROOT, folderName);
  const groups = groupPhotos(folderPath);

  for (const { title, files } of groups.values()) {
    const clientSlug = slugify(title);
    const ref = `${categoriaSlug}-${clientSlug}`;
    const destDir = join(MEDIA_ROOT, categoriaSlug, ref);
    mkdirSync(destDir, { recursive: true });

    const media = files.map((file, index) => {
      const nn = String(index + 1).padStart(2, "0");
      const destFile = join(destDir, `${nn}.jpg`);
      copyFileSync(join(folderPath, file), destFile);
      return `real:${categoriaSlug}/${ref}/${nn}`;
    });

    manifest.push({
      ref,
      titolo: titleCase(title),
      categoria: categoriaSlug,
      media,
    });
  }
}

writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2) + "\n", "utf8");
console.log(`Wrote ${manifest.length} historic progetti to ${MANIFEST_PATH}`);
console.log(`Copied photos into ${MEDIA_ROOT}/`);
```

- [ ] **Step 2: Run it**

```bash
mkdir -p cms/seed/media/realizzazioni
node scripts/seed/build-realizzazioni-media.mjs
```

Expected output: `Wrote 84 historic progetti to cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json` (count may differ slightly ± a couple, since a small number of ambiguous filename groups may split/merge marginally differently than the manual count in the design spec — that's fine, the manifest is the source of truth going forward).

- [ ] **Step 3: Manual review pass**

Run: `cat cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json | python3 -m json.tool | less`

Check specifically for the known edge cases and fix by hand-editing the generated JSON (not the script) if anything still looks wrong:
- `strutture-miste` / `opere-speciali` both contain a "Castellazzo Piscina" and a "Noceto" entry (different `ref` because `ref` includes the category slug) — confirm both are present and distinct, not merged.
- `scale` has one merged "Ricci Casa-apr07" entry with 3 photos (from the override).
- No entry titled after `P17-10-08_11.` exists (excluded).
- Skim titles for obviously-wrong capitalization (the acronym list only covers CNA/SIT/FBP/ZRH/CT/CLS — anything else looks odd is a 2-second manual edit in the JSON, e.g. `"titolo": "Fbp Piacenza"` → `"titolo": "FBP Piacenza"` if the acronym match missed a multi-word title).

- [ ] **Step 4: Verify file counts match the manifest**

```bash
find cms/seed/media/realizzazioni -name "*.jpg" | wc -l
python3 -c "import json; m=json.load(open('cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json')); print(sum(len(e['media']) for e in m))"
```

Expected: the two numbers match.

- [ ] **Step 5: Commit**

```bash
git add scripts/seed/build-realizzazioni-media.mjs cms/seed/media/realizzazioni cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json
git commit -m "feat(wp): import ~184 historic project photos, grouped into ~84 real progetti"
```

(Keep `sito web edilmetal 2018/` untouched in place — it's the raw source dump the script reads from, not shipped output. Do not delete it.)

---

### Task 19: `Catalog.php` — merge historic progetti, wire hero/evidenza/fax

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php`

**Interfaces:**
- Consumes: `realizzazioni-storiche.json` (Task 18), `MediaRef` (existing).
- Produces: `Catalog::progetti()` = curated (existing 12) + historic (new, from manifest). `Catalog::settings()` gains `fax` + a real `heroImage`. `Catalog::page_home()`'s meta gains hero index/cta2 + the 2 evidenza slots, loses `stats`/`intro`.

- [ ] **Step 1: Rename the existing method and add the historic loader**

Rename `public static function progetti(): array` to `private static function curated_progetti(): array` (body unchanged), then add:

```php
	/**
	 * Realizzazioni storiche reali (foto 1997–2018), caricate dal manifest
	 * generato da `scripts/seed/build-realizzazioni-media.mjs`.
	 *
	 * Dati tecnici (superficie/luce/altezza/peso) e descrizione restano
	 * segnaposto onesti: sono da compilare in WP admin, progetto per progetto.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function historic_progetti(): array {
		$path = __DIR__ . '/realizzazioni-storiche.json';

		if ( ! file_exists( $path ) ) {
			return array();
		}

		$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- file locale del repository, non remoto.
		$entries = null !== $raw ? json_decode( $raw, true ) : null;

		if ( ! is_array( $entries ) ) {
			return array();
		}

		$records = array();

		foreach ( $entries as $entry ) {
			$titolo = (string) ( $entry['titolo'] ?? '' );
			$ref    = (string) ( $entry['ref'] ?? '' );

			if ( '' === $titolo || '' === $ref ) {
				continue;
			}

			$records[] = self::progetto(
				$ref,
				$titolo,
				(string) ( $entry['categoria'] ?? '' ),
				'',
				$titolo,
				'Provincia di Parma',
				2018,
				false,
				sprintf( 'Realizzazione in carpenteria metallica per %s', $titolo ),
				null,
				null,
				null,
				null,
				array(),
				array(),
				is_array( $entry['media'] ?? null ) ? $entry['media'] : array()
			);
		}

		return $records;
	}

	/**
	 * Tutte le realizzazioni: curate a mano + storiche reali importate.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function progetti(): array {
		return array_merge( self::curated_progetti(), self::historic_progetti() );
	}
```

Note for the implementer: the historic records intentionally pass `''` for `settore` (optional in the frontend contract — `ProgettoSeeder::assign_terms()` already skips term assignment when `get_term_by('slug', '', ...)` returns `false`, no crash) and `'Provincia di Parma'`/`2018`/a generic `tipologia` sentence as honest, clearly-editable placeholders — matching the approved design spec (`docs/superpowers/specs/2026-07-22-restyle-homepage-contenuti-reali-design.md`).

- [ ] **Step 2: `settings()` — add `fax` and the real hero image**

In `Catalog::settings()`, change:

```php
					'edilmetal_set_telefono'        => '0521 615023',
```

to:

```php
					'edilmetal_set_telefono'        => '0521 615023',
					'edilmetal_set_fax'              => '0521 615207',
```

and change:

```php
					'edilmetal_set_hero_image'      => new MediaRef( 'insieme' ),
```

to:

```php
					'edilmetal_set_hero_image'      => new MediaRef( 'real:strutture-acciaio/strutture-acciaio-parmalat-collecchio/01' ),
```

Note for the implementer: this key must match exactly what Task 18's manifest produced for the Parmalat project in `1 strutture acciaio/` — before hardcoding it, confirm with:

```bash
python3 -c "
import json
m = json.load(open('cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json'))
matches = [e for e in m if 'parmalat' in e['ref']]
print(matches)
"
```

and use whichever `media[0]` value actually corresponds to the "home page/parmalat 1.jpg" shot for that project's `ref` — the manifest groups `parmalat collecchio 1..5.JPG` from `1 strutture acciaio/` separately from the `home page/parmalat 1.jpg` file (the latter isn't part of any category folder, so it's NOT in the manifest at all). Since the hero photo must be the specific "home page/parmalat 1.jpg" shot (the one matching the archived site's hero), copy it separately as a named seed asset instead of relying on the manifest:

```bash
mkdir -p cms/seed/media/home
cp "sito web edilmetal 2018/home page/parmalat 1.jpg" cms/seed/media/home/hero-parmalat.jpg
```

Then extend `RealPhotos::ensure()` (Task 17) to also scan `cms/seed/media/home/*.jpg` under keys `real:home/<stem>` (add a second `glob()` loop mirroring the categoria/progetto one, non-nested: `foreach (glob(trailingslashit($base).'home/*.jpg') ... as $file) { $paths["real:home/".pathinfo($file, PATHINFO_FILENAME)] = $file; }` where `$base` is the same root used for the `realizzazioni` scan). Then reference it here as:

```php
					'edilmetal_set_hero_image'      => new MediaRef( 'real:home/hero-parmalat' ),
```

- [ ] **Step 3: `page_home()` — rewrite the meta array**

Replace the whole `'meta'` array (and `'meta_en'`) with:

```php
				'meta'     => array(
					'edilmetal_home_hero_eyebrow'       => 'Carpenteria metallica su commessa · dal 1997',
					'edilmetal_home_hero_titolo'        => 'Strutture in acciaio',
					'edilmetal_home_hero_titolo_accent' => 'progettate, prodotte e montate.',
					'edilmetal_home_hero_sottotitolo'   => 'Progettazione dedicata, relazioni di calcolo firmate, produzione in officina e montaggio in cantiere per l\'edilizia industriale, commerciale e terziaria.',
					'edilmetal_home_hero_cta_label'     => 'Le realizzazioni',
					'edilmetal_home_hero_cta_url'       => '/realizzazioni',
					'edilmetal_home_hero_cta2_label'    => 'Richiedi un preventivo',
					'edilmetal_home_hero_cta2_url'      => '/contatti',
					'edilmetal_home_hero_index'         => array(
						'Dal 1997|Esperienza in cantiere',
						'Su commessa|Calcoli firmati da tecnici abilitati',
						'Noceto (PR)|Progettazione · produzione · montaggio',
					),
					'edilmetal_home_evidenza1_categoria' => 'strutture-acciaio',
					'edilmetal_home_evidenza1_img'       => new MediaRef( 'real:strutture-acciaio/strutture-acciaio-acetum/01' ),
					'edilmetal_home_evidenza2_categoria' => 'pensiline',
					'edilmetal_home_evidenza2_img'       => new MediaRef( 'real:pensiline/pensiline-ferrari/01' ),
				),
				'meta_en'  => array(
					'edilmetal_home_hero_eyebrow'       => 'Made-to-order structural steelwork · since 1997',
					'edilmetal_home_hero_titolo'        => 'Steel structures',
					'edilmetal_home_hero_titolo_accent' => 'designed, fabricated and assembled.',
					'edilmetal_home_hero_sottotitolo'   => 'Dedicated design, signed structural calculations, in-house fabrication and on-site assembly for industrial, commercial and tertiary construction.',
					'edilmetal_home_hero_cta_label'     => 'Our projects',
					'edilmetal_home_hero_cta2_label'    => 'Request a quote',
					'edilmetal_home_hero_index'         => array(
						'Since 1997|Experience on site',
						'Made to order|Calculations signed by qualified engineers',
						'Noceto (PR)|Design · fabrication · assembly',
					),
				),
```

Note for the implementer: `edilmetal_home_evidenza1_img`/`edilmetal_home_evidenza2_img` reference `MediaRef` keys — before hardcoding `strutture-acciaio-acetum` and `pensiline-ferrari`, confirm those exact `ref` values exist in the generated manifest (`grep -o '"ref": "[^"]*"' cms/mu-plugins/edilmetal-core/seed/Data/realizzazioni-storiche.json | grep -E "acetum|ferrari"`); adjust the two `MediaRef` keys to whatever `ref` the script actually produced for those two clients if the slugified name differs (e.g. spacing/accents).

- [ ] **Step 4: PHP lint**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/seed/Data/Catalog.php`
Expected: clean.

- [ ] **Step 5: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php cms/seed/media/home
git commit -m "feat(wp): seed real hero photo, 2 featured categories, fax and historic progetti"
```

---

## Part E — Local verification

### Task 20: Full local rebuild and page-by-page check

**Files:** none (verification only).

- [ ] **Step 1: Fresh Docker environment**

```bash
npm run dev:up
npm run dev:setup
```

Expected: WordPress reachable at `http://localhost:8890/wp-admin`, no container errors in `docker compose logs`.

- [ ] **Step 2: Seed with `--fresh` to force a clean re-import**

```bash
npm run dev:cli -- edilmetal seed --fresh
```

Expected: command reports importing the real photos (media count should jump to roughly 184 + 3 placeholders + 1 hero) and ~96 progetti (12 curated + ~84 historic). If it errors on a specific record, fix the offending manifest entry (Task 18 output) or `Catalog.php` wiring (Task 19) and re-run — do not silently swallow the error.

- [ ] **Step 3: REST sanity checks**

```bash
curl -s http://localhost:8890/wp-json/edilmetal/v1/pages/home | python3 -m json.tool | head -60
curl -s http://localhost:8890/wp-json/edilmetal/v1/settings | python3 -m json.tool
curl -s "http://localhost:8890/wp-json/edilmetal/v1/progetti?in_evidenza=1" | python3 -m json.tool | head -40
```

Expected: `pages/home` response has `hero.ctaPrimary.href` (not `.url`), `hero.index` with 3 entries, `inEvidenza` with 2 entries each carrying a real `immagine.src` URL; `settings` has `fax` and `heroImage`; `progetti?in_evidenza=1` returns the curated in-evidence projects with real `copertina` images.

- [ ] **Step 4: Frontend against real WordPress**

```bash
cd web
echo "WP_API_URL=http://localhost:8890/wp-json/edilmetal/v1" >> .env.local  # only if not already pointing there
npm run typecheck && npm run lint && npm run test && npm run build
npm run dev
```

Visit `http://localhost:3000/it` and, page by page, compare against the archived site (`https://web.archive.org/web/20240901125403/https://www.edilmetal.it/`) and the approved design:
- Home: real crane photo in the hero, "In evidenza" 2 category cards with real photos, dark "Realizzazioni" band with real project photos, Contatti/Orari/Telefono band, footer.
- `/realizzazioni`: full grid populated with ~96 real projects; clicking "Vai alla sezione prodotti" from a Home "In evidenza" card lands here pre-filtered to that category.
- A project detail page (`/realizzazioni/<slug>` for one of the historic ones, e.g. `strutture-miste-aiassa`): real gallery photos, placeholder `luogo`/`anno`/description clearly present and editable.
- `/servizi`, `/azienda`, `/contatti`: unchanged, still render (regression check — these were not touched, but they share `Footer`/`Header`/`ContactInfoBand` from the layout now).

- [ ] **Step 5: Accessibility spot-check**

If Playwright + axe is already wired in this repo (`docs/deploy-setup.md`/`package.json` — check for an `axe` or `e2e` script), run it against the home page. If no automated axe harness exists yet, do a manual check instead: every real photo has non-empty, descriptive `alt` text (verify via browser dev tools / view-source on a couple of pages), color contrast of the dark `RealizzazioniBand` text against `--deep` and of `ContactInfoBand` icons against `--bg-2` (both already reuse existing tokens at ratios validated elsewhere in the design system, but re-check visually).

- [ ] **Step 6: Report to the user**

Do not proceed to any deploy step. Summarize what changed, link the local URL, and explicitly ask the user to review before touching the GitHub Actions deploy workflow or the real domain — per the approved design's "Fuori scope" section.
