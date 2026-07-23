# Fix bug Servizi/Azienda + uniformità Realizzazioni — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Risolvere il bug preesistente per cui `/servizi` e `/azienda` restituiscono una pagina vuota contro WordPress reale (contratto DTO disallineato tra PHP e frontend), riallineando entrambi i lati SOLO ai contenuti minimi già decisi nel piano precedente (hero+elenco categorie per Servizi, hero+storia per Azienda) — non aggiungendo nulla che il sito storico non avesse. Inoltre rimuove la banda CTA finale da Realizzazioni per uniformità con le altre pagine sfoltite.

**Architecture:** Frontend (schema zod + mock) e backend WordPress (Meta Box fields + Presenter + seed) vengono riallineati nello stesso identico contratto minimale, eliminando sia il codice frontend orfano sia i campi WP che non servono più — non solo "non li leggo più", ma "non esistono più" dove possibile, per evitare di lasciare in giro editorialmente campi che implicano sezioni ormai inesistenti.

**Tech Stack:** Next.js (TS strict, zod, Vitest), WordPress PHP (Meta Box, Presenter/Repository pattern esistente).

## Global Constraints

- TS strict, no `any`; zod valida ogni DTO REST al confine.
- PHP: WordPress Coding Standards + PSR-12, `declare(strict_types=1)`, niente SQL diretto.
- Nessun contenuto hardcoded nel frontend oltre le label i18n.
- Ogni task frontend deve lasciare `cd web && npm run typecheck && npm run lint && npm run test && npm run build` verde.
- Ogni task PHP deve lasciare `cd cms && composer phpcs -- <file toccati>` e `php -l <file toccati>` puliti.
- Le pagine coinvolte (Servizi, Azienda) restano esattamente al contenuto già deciso nel piano "fedeltà al sito storico": Servizi = hero + elenco 8 categorie; Azienda = hero + storia testuale. Nessuna sezione nuova.

---

### Task 1: Frontend — semplifica `serviziContentSchema`/`aziendaContentSchema` e il mock

Il primo piano aveva sfoltito le PAGINE (`servizi/page.tsx`, `azienda/page.tsx`) ma non gli SCHEMI zod, che ancora dichiarano campi (`processo`, `vantaggi`, `callout`, `cta`, `stats`, `valori`, `officina*`, `sede*`) che nessuna pagina legge più. Questo task pulisce gli schemi per farli combaciare esattamente con quello che le pagine usano davvero, e aggiorna il mock di conseguenza.

**Files:**
- Modify: `web/src/domain/page.ts`
- Modify: `web/src/domain/page.test.ts`
- Modify: `web/src/lib/api/mock/pages.ts`

**Interfaces:**
- Produce: `ServiziContent = { tipologie: CategoriaRef[] }`. `AziendaContent = { storiaTitolo: string; storia: string[] }`.
- Consuma: `categoriaRefSchema` (esistente, invariato).

- [ ] **Step 1: Estendi il test di dominio**

Aggiungi in coda a `web/src/domain/page.test.ts` (se il file non esiste ancora, crealo con questo contenuto; se esiste, aggiungi questi `describe` accanto a quelli già presenti):

```ts
import { describe, expect, it } from "vitest";
import { aziendaContentSchema, serviziContentSchema } from "./page";

describe("serviziContentSchema", () => {
  it("accepts only { tipologie }", () => {
    const parsed = serviziContentSchema.parse({
      tipologie: [{ slug: "scale", nome: "Scale", dettaglio: "Interne · esterne" }],
    });
    expect(parsed.tipologie).toHaveLength(1);
  });

  it("defaults tipologie to an empty array when absent", () => {
    const parsed = serviziContentSchema.parse({});
    expect(parsed.tipologie).toEqual([]);
  });
});

describe("aziendaContentSchema", () => {
  it("accepts only { storiaTitolo, storia }", () => {
    const parsed = aziendaContentSchema.parse({
      storiaTitolo: "Dal 1997.",
      storia: ["Primo paragrafo.", "Secondo paragrafo."],
    });
    expect(parsed.storia).toHaveLength(2);
  });

  it("requires storiaTitolo (non-empty)", () => {
    expect(() => aziendaContentSchema.parse({ storia: [] })).toThrow();
  });
});
```

- [ ] **Step 2: Esegui i test per verificare il fallimento**

Run: `cd web && npx vitest run src/domain/page.test.ts`
Expected: FAIL — gli schemi attuali richiedono ancora `cta`/`officinaTitolo`/`sedeTitolo` (obbligatori), quindi il payload minimale del test viene rifiutato o produce un tipo con campi extra non testati.

- [ ] **Step 3: Riscrivi gli schemi**

In `web/src/domain/page.ts`, sostituisci il blocco `/* SERVIZI */` con:

```ts
/* -------------------------------------------------------------------------- */
/* SERVIZI                                                                     */
/* -------------------------------------------------------------------------- */

export const serviziContentSchema = z.object({
  tipologie: z.array(categoriaRefSchema).default([]),
});
export type ServiziContent = z.infer<typeof serviziContentSchema>;
```

e il blocco `/* AZIENDA */` con:

```ts
/* -------------------------------------------------------------------------- */
/* AZIENDA                                                                     */
/* -------------------------------------------------------------------------- */

export const aziendaContentSchema = z.object({
  storiaTitolo: z.string().min(1),
  storia: z.array(z.string().min(1)).default([]),
});
export type AziendaContent = z.infer<typeof aziendaContentSchema>;
```

Poi rimuovi, dal blocco `/* Blocchi condivisi */` in cima al file, i tipi ora orfani: `stepSchema`/`Step`, `featureSchema`/`Feature`, `calloutSchema`/`Callout`, `ctaBandSchema`/`CtaBand` — MA PRIMA verifica con:

```bash
grep -rln "stepSchema\|: Step\b\|featureSchema\|: Feature\b\|calloutSchema\|: Callout\b\|ctaBandSchema\|: CtaBand\b" web/src --include="*.ts" --include="*.tsx" | grep -v "domain/page.ts\|domain/page.test.ts"
```

che nessun altro file (a parte `mock/pages.ts`, che aggiornerai nello Step 5) li usi ancora. Se il grep restituisce risultati imprevisti in altri file, NON rimuovere quel tipo — lascialo e segnalalo nel report. `statSchema`/`Stat` e `ctaSchema`/`Cta` restano (usati da `homeHeroSchema`).

- [ ] **Step 4: Esegui i test per verificare che passino**

Run: `cd web && npx vitest run src/domain/page.test.ts`
Expected: PASS (4 nuovi test, più quelli preesistenti nel file se già ce n'erano).

- [ ] **Step 5: Aggiorna il mock**

In `web/src/lib/api/mock/pages.ts`:
1. Cambia l'import in cima da `import type { CategoriaRef, Feature, PageContent, Step } from "@/domain";` a `import type { CategoriaRef, PageContent } from "@/domain";`.
2. Rimuovi le costanti `PROCESSO` e `PERCHE` (non più usate da nessuna pagina).
3. Sostituisci l'oggetto `servizi` con:

```ts
const servizi: PageContent = {
  key: "servizi",
  title: "Cosa facciamo",
  subtitle:
    "Progettazione, costruzione e montaggio di strutture in carpenteria metallica per l'edilizia industriale, commerciale e terziaria. Un unico interlocutore, dal sopralluogo al post-vendita.",
  servizi: {
    tipologie: CATEGORIE,
  },
};
```

4. Sostituisci l'oggetto `azienda` con:

```ts
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
```

`CATEGORIE` resta invariata (ancora usata da `servizi.tipologie`).

- [ ] **Step 6: Verifica completa**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde. Il typecheck è la verifica cruciale: se `servizi/page.tsx`/`azienda/page.tsx` (già sfoltite in un piano precedente) referenziassero un campo ora rimosso dallo schema, emergerebbe qui — ma non dovrebbero, perché leggono già solo `tipologie`/`storiaTitolo`/`storia`.

- [ ] **Step 7: Commit**

```bash
git add web/src/domain/page.ts web/src/domain/page.test.ts web/src/lib/api/mock/pages.ts
git commit -m "fix(domain): trim ServiziContent/AziendaContent schemas to only the fields the pages read"
```

---

### Task 2: Frontend — rimuovi la banda CTA da Realizzazioni e il codice ora orfano

Il sito storico non aveva una banda "Richiedi un preventivo" nelle pagine di realizzazioni per categoria. La rimuoviamo per uniformità con Servizi/Azienda (già sfoltite). Questo lascia il componente `CtaBand` (in `blocks.tsx`) e il tipo di dominio `ctaBandSchema`/`CtaBand` (se non già rimosso dal Task 1) senza chiamanti: vanno ripuliti anche loro.

**Files:**
- Modify: `web/src/app/[locale]/realizzazioni/page.tsx`
- Modify: `web/src/components/ui/blocks.tsx`
- Modify: `web/src/i18n/messages/it.json`
- Modify: `web/src/i18n/messages/en.json`

**Interfaces:** Nessuna nuova — solo rimozione.

- [ ] **Step 1: Rimuovi la banda CTA dalla pagina**

In `web/src/app/[locale]/realizzazioni/page.tsx`, rimuovi l'import `CtaBand` da `@/components/ui/blocks` (mantieni `SectionLabel` se ancora usato — verificalo leggendo il file) e rimuovi l'intero blocco:

```tsx
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
```

Se dopo questa rimozione `tNav`/`getTranslations("Nav")` non serve più a nient'altro nel file, rimuovi anche quell'import/variabile (verifica leggendo il resto del file prima di toccarlo).

- [ ] **Step 2: Rimuovi `CtaBand` da `blocks.tsx` — solo se confermato orfano**

Esegui:

```bash
grep -rln "<CtaBand\b" web/src/app web/src/components | grep -v "\.test\."
```

Se il risultato è vuoto (nessun altro chiamante), rimuovi la funzione `CtaBand` da `web/src/components/ui/blocks.tsx`. Se il grep restituisce ancora qualche file, NON rimuoverla e segnalalo nel report (significherebbe che un'altra pagina la usa ancora, non prevista da questo piano).

- [ ] **Step 3: Rimuovi le chiavi i18n `ctaTitle`/`ctaText` dal namespace `Realizzazioni`, se ora orfane**

Verifica con:

```bash
grep -rn '"Realizzazioni"' -A 30 web/src/i18n/messages/it.json | grep -c "ctaTitle\|ctaText"
grep -rn "t(\"ctaTitle\")\|t('ctaTitle')\|t(\"ctaText\")\|t('ctaText')" web/src/app/\[locale\]/realizzazioni
```

Se dopo lo Step 1 nessun file usa più `t("ctaTitle")`/`t("ctaText")` nel contesto di Realizzazioni, rimuovi `ctaTitle`/`ctaText` dall'oggetto `"Realizzazioni"` in `web/src/i18n/messages/it.json` ED `en.json`.

- [ ] **Step 4: Verifica completa**

Run: `cd web && npm run typecheck && npm run lint && npm run test && npm run build`
Expected: tutto verde. Ispeziona `web/out/it/realizzazioni/index.html` per confermare l'assenza del testo della CTA rimossa.

- [ ] **Step 5: Commit**

```bash
git add web/src/app/\[locale\]/realizzazioni/page.tsx web/src/components/ui/blocks.tsx web/src/i18n/messages/it.json web/src/i18n/messages/en.json
git commit -m "feat(realizzazioni): drop the closing CTA band, matching Servizi/Azienda already stripped to historic content"
```

---

### Task 3: PHP — la tassonomia `categoria_opera` guadagna una descrizione per termine

Serve a popolare `dettaglio` in `CategoriaRef` senza un campo WP separato: la pagina Servizi elenca le 8 categorie reali della tassonomia (non un elenco testuale libero scollegato), proprio come il vecchio "Prodotti".

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php`
- Modify: `cms/mu-plugins/edilmetal-core/seed/TaxonomySeeder.php`

**Interfaces:**
- Produce: `TaxonomySeeder::seed()` scrive anche la `description` di ogni termine `categoria_opera` (letta poi da `PagePresenter` nel Task 5 via `$term->description`).

- [ ] **Step 1: Aggiungi le descrizioni ai termini nel catalogo**

In `Catalog::taxonomies()`, nel blocco `'categoria_opera' => array(...)`, aggiungi la chiave `'description'` a ciascuno degli 8 elementi esistenti (non toccare `'settore'`, che non ne ha bisogno). Il blocco esistente ha questa forma per ogni voce:

```php
array(
    'slug' => 'strutture-acciaio',
    'name' => 'Strutture in acciaio',
),
```

Trasformala in:

```php
array(
    'slug'        => 'strutture-acciaio',
    'name'        => 'Strutture in acciaio',
    'description' => 'Capannoni · soppalchi · edifici industriali',
),
```

Applica lo stesso pattern (aggiungendo solo la chiave `description`, senza toccare slug/name) alle altre 7 voci, con questi testi:

| slug | description |
|---|---|
| `strutture-miste` | `Acciaio-calcestruzzo · ampliamenti` |
| `scale` | `Interne · esterne · di sicurezza` |
| `pensiline` | `Industriali · di ingresso · di carico` |
| `pensiline-auto` | `Aree di sosta · fotovoltaico` |
| `coperture-tamponamenti` | `Pannelli · lamiere · isolamento` |
| `rivestimenti-facciata` | `Frangisole · lamiere forate · finiture` |
| `opere-speciali` | `Su disegno · carpenteria di dettaglio` |

- [ ] **Step 2: Fai scrivere la descrizione al seeder**

In `TaxonomySeeder.php`, cambia la firma e il corpo di `seed()`:

```php
	public function seed(): int {
		$created = 0;

		foreach ( Catalog::taxonomies() as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				$description = (string) ( $term['description'] ?? '' );

				if ( $this->ensure_term( $taxonomy, (string) $term['slug'], (string) $term['name'], $description ) ) {
					++$created;
				}
			}
		}

		return $created;
	}
```

e `ensure_term()`:

```php
	private function ensure_term( string $taxonomy, string $slug, string $name, string $description = '' ): bool {
		$existing = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing instanceof \WP_Term ) {
			$changes = array();

			if ( $existing->name !== $name ) {
				$changes['name'] = $name;
			}

			if ( '' !== $description && $existing->description !== $description ) {
				$changes['description'] = $description;
			}

			if ( array() !== $changes ) {
				wp_update_term( $existing->term_id, $taxonomy, $changes );
			}

			return false;
		}

		$args = array( 'slug' => $slug );

		if ( '' !== $description ) {
			$args['description'] = $description;
		}

		wp_insert_term( $name, $taxonomy, $args );

		return true;
	}
```

Aggiorna anche il PHPDoc del parametro nuovo di `ensure_term` (`@param string $description Descrizione breve del termine (usata come "dettaglio" nel frontend).`).

- [ ] **Step 3: Verifica**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/seed/Data/Catalog.php mu-plugins/edilmetal-core/seed/TaxonomySeeder.php` e `php -l` su entrambi.

- [ ] **Step 4: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php cms/mu-plugins/edilmetal-core/seed/TaxonomySeeder.php
git commit -m "feat(wp): seed a short description per categoria_opera term, used as CategoriaRef.dettaglio"
```

---

### Task 4: PHP — allinea i campi Meta Box di Servizi e Azienda al contenuto minimo

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/fields/PageFields.php`

**Interfaces:**
- Produce meta key consumati dal Task 5: `edilmetal_servizi_sottotitolo` (già esistente, riusato), `edilmetal_azienda_sottotitolo` (già esistente, riusato), `edilmetal_azienda_storia` (già esistente, riusato), `edilmetal_azienda_storia_titolo` (NUOVO).

- [ ] **Step 1: Riscrivi `servizi_box()`**

Sostituisci l'intero metodo con:

```php
	/**
	 * Pagina Servizi / "Prodotti": sottotitolo di testata + SEO.
	 * L'elenco delle categorie (tipologie) arriva dalla tassonomia
	 * `categoria_opera`, non da un campo separato — niente da tenere in sync.
	 *
	 * @return array<string,mixed>
	 */
	private function servizi_box(): array {
		$fields = array(
			$this->textarea( 'edilmetal_servizi_sottotitolo', __( 'Sottotitolo di testata', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_servizi' ) );

		return $this->box( 'edilmetal_page_servizi', __( 'Contenuti: Servizi', 'edilmetal-core' ), $fields );
	}
```

- [ ] **Step 2: Riscrivi `azienda_box()`**

Sostituisci l'intero metodo con:

```php
	/**
	 * Pagina Azienda / "Chi siamo": sottotitolo di testata + storia + SEO.
	 * Sede, mappa e orari restano nelle impostazioni globali (non usati qui).
	 *
	 * @return array<string,mixed>
	 */
	private function azienda_box(): array {
		$fields = array(
			$this->textarea( 'edilmetal_azienda_sottotitolo', __( 'Sottotitolo di testata', 'edilmetal-core' ) ),
			$this->text( 'edilmetal_azienda_storia_titolo', __( 'Storia — Titolo (es. "La società")', 'edilmetal-core' ) ),
			$this->wysiwyg( 'edilmetal_azienda_storia', __( 'Storia aziendale (un paragrafo per blocco)', 'edilmetal-core' ) ),
		);

		$fields = array_merge( $fields, $this->seo_fields( 'edilmetal_azienda' ) );

		return $this->box( 'edilmetal_page_azienda', __( 'Contenuti: Azienda', 'edilmetal-core' ), $fields );
	}
```

- [ ] **Step 3: Verifica**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/fields/PageFields.php` e `php -l` sullo stesso file.

- [ ] **Step 4: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/fields/PageFields.php
git commit -m "feat(wp): trim Servizi/Azienda Meta Box fields to subtitle + storia, drop unused sections"
```

---

### Task 5: PHP — riscrivi i case `servizi`/`azienda` del Presenter

Questo è il cuore del fix: annida correttamente il DTO sotto la chiave di pagina (come già fatto per `home` in un commit precedente di questo stesso branch) E allinea la forma dei dati a quella che il frontend si aspetta davvero.

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php`

**Interfaces:**
- Consuma: `Schema::TAX_CATEGORIA` (esistente), campi Task 4.
- Produce: `dto['subtitle']` (stringa, top-level, se valorizzato), `dto['servizi'] = { tipologie: [{slug,nome,dettaglio}] }`, `dto['azienda'] = { storiaTitolo, storia: string[] }`. Forma identica a `serviziContentSchema`/`aziendaContentSchema` del Task 1.

- [ ] **Step 1: Aggiorna il case `servizi`**

Sostituisci:

```php
			case 'servizi':
				$dto['hero'] = $this->page_hero( 'edilmetal_servizi' );
				$this->maybe_block( $dto, 'intro', $this->intro( 'edilmetal_servizi_intro' ) );
				$dto['flow']      = $this->cards( 'edilmetal_servizi_flow' );
				$dto['tipologie'] = $this->cards( 'edilmetal_servizi_tipologie' );
				$this->maybe_block( $dto, 'callout', $this->callout( 'edilmetal_servizi_callout' ) );
				break;
```

con:

```php
			case 'servizi':
				$this->maybe( $dto, 'subtitle', $this->meta->string( 'edilmetal_servizi_sottotitolo' ) );
				$dto['servizi'] = array( 'tipologie' => $this->categoria_terms() );
				break;
```

- [ ] **Step 2: Aggiorna il case `azienda`**

Sostituisci:

```php
			case 'azienda':
				$dto['hero']   = $this->page_hero( 'edilmetal_azienda' );
				$dto['storia'] = $this->html( 'edilmetal_azienda_storia' );
				$dto['valori'] = $this->cards( 'edilmetal_azienda_valori' );
				$dto['team']   = $this->pairs( 'edilmetal_azienda_team', 'nome', 'ruolo' );
				$dto['stats']  = $this->stats( 'edilmetal_azienda_stats' );
				break;
```

con:

```php
			case 'azienda':
				$this->maybe( $dto, 'subtitle', $this->meta->string( 'edilmetal_azienda_sottotitolo' ) );
				$dto['azienda'] = array(
					'storiaTitolo' => $this->fallback( $this->meta->string( 'edilmetal_azienda_storia_titolo' ), 'La società' ),
					'storia'       => $this->storia_paragraphs( 'edilmetal_azienda_storia' ),
				);
				break;
```

- [ ] **Step 3: Aggiungi i tre metodi privati nuovi**

Aggiungi in fondo alla classe (prima dell'ultima `}` di chiusura), rispettando lo stile PHPDoc esistente:

```php
	/**
	 * Termini della tassonomia `categoria_opera`, in ordine canonico, nella
	 * forma { slug, nome, dettaglio } (dettaglio = descrizione del termine,
	 * o il nome stesso se non valorizzata).
	 *
	 * @return array<int,array<string,string>>
	 */
	private function categoria_terms(): array {
		$order = array(
			'strutture-acciaio',
			'strutture-miste',
			'scale',
			'pensiline',
			'pensiline-auto',
			'coperture-tamponamenti',
			'rivestimenti-facciata',
			'opere-speciali',
		);

		$terms = get_terms(
			array(
				'taxonomy'   => Schema::TAX_CATEGORIA,
				'hide_empty' => false,
			)
		);

		if ( ! is_array( $terms ) ) {
			return array();
		}

		$by_slug = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$by_slug[ $term->slug ] = $term;
			}
		}

		$out = array();
		foreach ( $order as $slug ) {
			if ( ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}

			$term = $by_slug[ $slug ];

			$out[] = array(
				'slug'      => $term->slug,
				'nome'      => $term->name,
				'dettaglio' => '' !== $term->description ? $term->description : $term->name,
			);
		}

		return $out;
	}

	/**
	 * Spezza un campo WYSIWYG in un elenco di paragrafi di solo testo.
	 * Ogni `<p>` diventa una voce; se il contenuto non contiene paragrafi
	 * HTML, l'intero testo (ripulito) diventa un unico paragrafo.
	 *
	 * @param string $key Meta key completa del campo WYSIWYG.
	 * @return string[]
	 */
	private function storia_paragraphs( string $key ): array {
		$html = $this->meta->string( $key );

		if ( '' === $html ) {
			return array();
		}

		if ( false === strpos( $html, '<p' ) ) {
			$text = trim( wp_strip_all_tags( $html ) );

			return '' !== $text ? array( $text ) : array();
		}

		preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $html, $matches );

		$paragraphs = array();
		foreach ( $matches[1] as $inner ) {
			$text = trim( wp_strip_all_tags( $inner ) );

			if ( '' !== $text ) {
				$paragraphs[] = $text;
			}
		}

		return $paragraphs;
	}

	/**
	 * Restituisce il valore se non vuoto, altrimenti il fallback indicato.
	 *
	 * @param string $value    Valore letto.
	 * @param string $fallback Valore di riserva.
	 */
	private function fallback( string $value, string $fallback ): string {
		return '' !== trim( $value ) ? $value : $fallback;
	}
```

- [ ] **Step 4: Rimuovi i metodi ora orfani, SOLO se confermato**

`page_hero()`, `intro()`, `cards()` (usato anche da `stats()`? verifica), `callout()` potrebbero essere usati ancora da altri case (`contatti` usa `page_hero`) — NON rimuovere nulla senza prima verificare con una lettura completa del file quali metodi restano chiamati da almeno un case. Se `cards()`/`callout()` risultano usati SOLO dai due case appena riscritti, rimuovili; se `page_hero()`/`intro()` sono usati anche da `contatti`, lasciali intatti.

- [ ] **Step 5: Verifica**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php` e `php -l` sullo stesso file.

- [ ] **Step 6: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/rest/Presenters/PagePresenter.php
git commit -m "fix(wp): nest servizi/azienda DTOs under their page key, align shape to the frontend schema"
```

---

### Task 6: PHP — allinea il seed dei contenuti Servizi/Azienda ai nuovi campi

**Files:**
- Modify: `cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php`

**Interfaces:** Nessuna nuova — solo dati.

- [ ] **Step 1: Aggiorna `page_servizi()`**

Sostituisci l'intero metodo con:

```php
	/**
	 * Pagina Servizi / Cosa facciamo (sottotitolo di testata; l'elenco
	 * categorie arriva dalla tassonomia, vedi Catalog::taxonomies()).
	 *
	 * @return array<string,mixed>
	 */
	private static function page_servizi(): array {
		return array(
			'key'      => 'servizi',
			'title'    => 'Servizi',
			'title_en' => 'Services',
			'meta'     => array(
				'edilmetal_servizi_sottotitolo' => 'Progettazione, costruzione e montaggio di strutture in carpenteria metallica per l\'edilizia industriale, commerciale e terziaria. Un unico interlocutore, dal sopralluogo al post-vendita.',
			),
			'meta_en'  => array(
				'edilmetal_servizi_sottotitolo' => 'Design, fabrication and assembly of structural steelwork for industrial, commercial and tertiary construction. A single point of contact, from survey to after-sales.',
			),
		);
	}
```

- [ ] **Step 2: Aggiorna `page_azienda()`**

Sostituisci l'intero metodo con:

```php
	/**
	 * Pagina Azienda (Chi siamo). Sede, mappa e orari restano nelle
	 * impostazioni globali.
	 *
	 * @return array<string,mixed>
	 */
	private static function page_azienda(): array {
		return array(
			'key'      => 'azienda',
			'title'    => 'Azienda',
			'title_en' => 'Company',
			'meta'     => array(
				'edilmetal_azienda_sottotitolo'   => 'Fondata a Noceto (PR) da Alessio Ricci e Aldo Medioli, Edilmetal realizza strutture in acciaio su commessa per clienti industriali e prestigiosi.',
				'edilmetal_azienda_storia_titolo' => 'La società.',
				'edilmetal_azienda_storia'        => '<p>Edilmetal S.r.l. nasce nel 1997 dall\'iniziativa di Alessio Ricci e Aldo Medioli. Da allora l\'azienda progetta, produce e monta strutture in carpenteria metallica per l\'edilizia industriale, commerciale e terziaria, sia in nuova costruzione sia in ristrutturazione.</p><p>Lavoriamo su commessa, con progettazione dedicata e relazioni di calcolo firmate da tecnici abilitati iscritti agli albi. Dal sopralluogo al montaggio siamo l\'unico interlocutore del cliente, con assistenza post-vendita.</p>',
			),
			'meta_en'  => array(
				'edilmetal_azienda_sottotitolo'   => 'Founded in Noceto (PR) by Alessio Ricci and Aldo Medioli, Edilmetal builds made-to-order steel structures for industrial and prestigious clients.',
				'edilmetal_azienda_storia_titolo' => 'The company.',
				'edilmetal_azienda_storia'        => '<p>Edilmetal S.r.l. was founded in 1997 by Alessio Ricci and Aldo Medioli. Since then the company has designed, fabricated and assembled structural steelwork for industrial, commercial and tertiary construction, both new builds and renovations.</p><p>We work to order, with dedicated design and structural calculations signed by qualified engineers. From survey to assembly we are the client\'s single point of contact, with after-sales support.</p>',
			),
		);
	}
```

- [ ] **Step 2: Verifica**

Run: `cd cms && composer phpcs -- mu-plugins/edilmetal-core/seed/Data/Catalog.php` e `php -l` sullo stesso file.

- [ ] **Step 3: Commit**

```bash
git add cms/mu-plugins/edilmetal-core/seed/Data/Catalog.php
git commit -m "feat(wp): align seeded Servizi/Azienda content to the new minimal field set"
```

---

### Task 7: Aggiorna `docs/api-contract.md`

**Files:**
- Modify: `docs/api-contract.md`

- [ ] **Step 1: Aggiorna le sezioni Servizi/Azienda**

Trova (`grep -n "servizi\|azienda" docs/api-contract.md`) e sostituisci la documentazione di `ServiziContent`/`AziendaContent` con:

```
ServiziContent {
  tipologie: Array<{ slug: CategoriaSlug; nome: string; dettaglio: string }>
}
// dettaglio = descrizione del termine tassonomia categoria_opera (o il nome se non valorizzata)

AziendaContent {
  storiaTitolo: string
  storia: string[]   // un paragrafo per elemento, derivato spezzando il campo WYSIWYG sui tag <p>
}
```

Rimuovi ogni riferimento documentato a `processo`/`vantaggi`/`callout`/`cta` sotto Servizi e a `stats`/`valori`/`officina*`/`sede*`/`cta` sotto Azienda, se presenti.

- [ ] **Step 2: Commit**

```bash
git add docs/api-contract.md
git commit -m "docs: update Servizi/Azienda contract to the minimal historic-fidelity shape"
```

---

### Task 8: Verifica end-to-end locale (eseguita direttamente, non da un subagent)

Questo task NON va delegato a un implementer — richiede un ambiente Docker già attivo e un giudizio visivo. Chi esegue il piano (l'orchestratore) lo fa direttamente.

- [ ] Rigenera il seed: `npm run dev:seed -- --fresh` (o senza `--fresh` se il flag non è supportato — verifica `SeedCommand.php`).
- [ ] `curl -s http://localhost:8890/wp-json/edilmetal/v1/pages/servizi | python3 -m json.tool` → deve mostrare `"servizi": { "tipologie": [ ...8 voci... ] }` annidato, con `slug`/`nome`/`dettaglio` per ciascuna delle 8 categorie nell'ordine canonico.
- [ ] `curl -s http://localhost:8890/wp-json/edilmetal/v1/pages/azienda | python3 -m json.tool` → deve mostrare `"azienda": { "storiaTitolo": "...", "storia": ["...", "..."] }` annidato, con `storia` come array di 2 paragrafi di solo testo (nessun tag HTML).
- [ ] `cd web && npm run build` con `.env.local` puntato al WP Docker reale → verifica che `/it/servizi` ed `/it/azienda` NON siano più pagine 404 vuote (ispeziona `web/out/it/servizi/index.html` e `web/out/it/azienda/index.html`: devono contenere il markup reale, non `__next_error__`).
- [ ] Controllo visivo in browser (`npm run dev` + Playwright) di `/it/servizi`, `/it/azienda`, `/it/realizzazioni` (CTA finale assente).
- [ ] Riporta l'esito all'utente prima di considerare il lavoro concluso.

## Self-Review

- **Copertura**: bug DTO servizi/azienda risolto (Task 3-6) ✓, CtaBand rimossa da Realizzazioni (Task 2) ✓, schemi frontend allineati (Task 1) ✓, verifica end-to-end (Task 8) ✓.
- **Coerenza tipi**: `ServiziContent`/`AziendaContent` (Task 1) sono la stessa identica forma che `PagePresenter` produce (Task 5) — nomi campo (`tipologie`, `storiaTitolo`, `storia`) e struttura (`{slug,nome,dettaglio}`) verificati identici in entrambi i task.
- **Nessun placeholder**: ogni step ha codice PHP/TS completo, comandi e output attesi espliciti.
