# Ordine gallery realizzazioni

Il manifest `realizzazioni-storiche.json` conserva tutti i riferimenti nel loro
ordine canonico: per le gallery multiple la prima immagine è il risultato
finale. Catalogo e script di sincronizzazione rispettano quell'ordine senza
riordinarlo. I file restano invariati.

Per le installazioni già seedate eseguire prima la verifica:

`wp eval-file cms/seed/sync-realizzazioni-gallery.php`

Poi applicare l'aggiornamento e ripetere la verifica:

`wp eval-file cms/seed/sync-realizzazioni-gallery.php apply`

Lo script salta le gallery con allegati diversi dal manifest e riporta allegati
o progetti mancanti. Aggiorna solo ordine gallery, thumbnail e Open Graph.

## Realizzazioni con una sola foto

Queste 31 gallery restano invariate:

- strutture-acciaio: `albinea`, `castellazzo-piscina`, `oiki`,
  `parmalat-medioli`, `schiaretti`;
- scale: `ct-reggio`, `doppel-scala`, `immergas-mag07`, `italbox-scala`,
  `scala-max-mara`, `scala-s-agostino`, `ubaldi`;
- pensiline: `padana-gru`, `acmi-rubbiano`, `conad`, `consultimpianti`,
  `doppel-passerella`, `immergas-mag07`, `new-house`, `pinko`,
  `ricci-pensiline`;
- pensiline-auto: `tagliavini`;
- coperture-tamponamenti: `amoretti`, `iton`, `mazzoni`, `zecchetti-x`,
  `zecchetti-y`;
- opere-speciali: `cna`, `unieco`, `unieco-bs`, `via-spezia`.

## Verifiche fotografiche

Non sono state individuate anomalie dall'ordine dei file. La conferma visiva
manuale delle foto finali resta da completare nell'ambiente con il patrimonio
fotografico disponibile.
