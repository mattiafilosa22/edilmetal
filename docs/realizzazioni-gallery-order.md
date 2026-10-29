# Ordine gallery realizzazioni

Il manifest `realizzazioni-storiche.json` conserva tutti i riferimenti nel loro
ordine canonico: per le gallery multiple la prima immagine è il risultato
finale e le foto di cantiere chiudono la sequenza. Di norma è l'ordine inverso
dei file; 5 gallery hanno un ordine curato a mano dopo la verifica visiva
(vedi sotto). Catalogo e script di sincronizzazione rispettano quell'ordine senza
riordinarlo. I file restano invariati.

Per le installazioni già seedate eseguire prima la verifica:

`wp eval-file cms/seed/sync-realizzazioni-gallery.php`

Poi applicare l'aggiornamento e ripetere la verifica:

`wp eval-file cms/seed/sync-realizzazioni-gallery.php apply`

Lo script salta le gallery con allegati diversi dal manifest e riporta allegati
o progetti mancanti. Per le gallery con gli stessi allegati, aggiorna
indipendentemente ordine, thumbnail e Open Graph.

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

Il test degli asset locali conferma che tutti i 183 riferimenti del manifest
esistono e che le 51 gallery multiple sono in ordine decrescente, salvo le
gallery curate sotto.

La verifica visiva delle 51 gallery multiple è stata eseguita. In 5 casi l'ultimo
file era ancora una foto di cantiere mentre l'opera finita compariva prima;
l'ordine è definito in `CURATED_MEDIA_ORDER`
(`scripts/seed/build-realizzazioni-media.mjs`) e verificato dal test:

| Realizzazione | Ordine file |
| --- | --- |
| `strutture-acciaio-speedy-block` | 01, 03, 02 |
| `pensiline-furia` | 01, 02 |
| `pensiline-italbox-pensilina` | 01, 02 |
| `coperture-tamponamenti-mercantile` | 02, 03, 05, 04, 01 |
| `rivestimenti-facciata-meta` | 04, 02, 01, 05, 03 |

Non hanno una foto dell'opera finita, quindi apre la foto di cantiere più
avanzata: `strutture-acciaio-parmalat-collecchio`, `strutture-acciaio-toscanini`,
`strutture-miste-aiassa`, `pensiline-ferrari`,
`coperture-tamponamenti-copertura-collecchio`, `rivestimenti-facciata-fbp-piacenza`.
