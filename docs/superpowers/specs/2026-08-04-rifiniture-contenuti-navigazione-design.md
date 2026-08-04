# Rifiniture contenuti e navigazione — Design

## Obiettivo

Allineare quattro aree del frontend agli screenshot approvati: semplificare la pagina Azienda, eliminare la descrizione duplicata dalla testata delle schede progetto, garantire che le card delle realizzazioni portino al relativo dettaglio e aggiornare il testo della modale di avviso.

## Soluzione approvata

- La pagina Azienda continua a mostrare i paragrafi della storia ma non renderizza `azienda.storiaTitolo` (nello screenshot: “La società”). Il dato resta disponibile nel CMS e nel contratto API.
- La scheda progetto non renderizza più `progetto.descrizione` nella testata accanto al titolo. La descrizione resta disponibile nel tab dedicato, nei metadati e nel JSON-LD.
- Ogni `ProjectCard`, sia nelle realizzazioni in evidenza sia nei progetti correlati, è un unico link accessibile verso `/{locale}/realizzazioni/{slug}`. Immagine, titolo e metadati fanno parte della stessa area cliccabile. Il trascinamento orizzontale della rail continua a non attivare accidentalmente il link.
- La modale non mostra più il kicker “00 Avviso”. Il titolo italiano diventa “Stiamo lavorando per rendere il sito ancora più fruibile.” e il testo resta “Sito in aggiornamento.”. La variante inglese mantiene la stessa struttura semantica con una traduzione coerente.

## Confini e accessibilità

Non vengono modificati CMS, DTO, URL, comportamento di chiusura della modale o persistenza in `sessionStorage`. Il titolo della modale mantiene `aria-labelledby`; il pulsante di chiusura conserva la propria etichetta accessibile. La rimozione dei testi avviene dal markup, non tramite CSS.

## Verifica

Test di componente coprono copy e assenza del kicker nella modale, link completo della card e assenza dei blocchi rimossi dalle due pagine. La consegna richiede test Vitest completi, typecheck, ESLint e build statico. Il deploy avviene tramite push del branch e avvio manuale del workflow GitHub Actions `deploy-plesk.yml`, con verifica del risultato.
