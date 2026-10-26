# Ordine gallery realizzazioni

Il manifest `realizzazioni-storiche.json` conserva tutti i riferimenti; il
catalogo e lo script di sincronizzazione ordinano le chiavi in modo decrescente,
così la prima immagine è il risultato finale. I file restano invariati.

Per le installazioni già seedate eseguire prima la verifica:

`wp eval-file cms/seed/sync-realizzazioni-gallery.php`

Poi applicare l'aggiornamento e ripetere la verifica:

`wp eval-file cms/seed/sync-realizzazioni-gallery.php apply`

Lo script salta le gallery con allegati diversi dal manifest e riporta allegati
o progetti mancanti. Aggiorna solo ordine gallery, thumbnail e Open Graph.
