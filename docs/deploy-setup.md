# Deploy — configurazione (Plesk + GitHub Actions + webhook WP)

Pipeline: modifica in WordPress → webhook `repository_dispatch` → GitHub Actions builda l'export statico → **SFTP/rsync su Plesk** (document root del dominio) → il sito statico si aggiorna.

Il WordPress headless gira su un **sottodominio** (es. `cms.<dominio>`) sullo stesso hosting Plesk (PHP + MySQL). Il frontend statico è servito dal dominio principale.

> Valori concreti (dominio, host SFTP, utente, path) da compilare quando il cliente fornisce gli accessi Plesk. I placeholder qui sotto vanno sostituiti nei GitHub Secrets / in `scripts/.plesk.env` (non tracciato).

## 1. GitHub Secrets (repo Settings → Secrets and variables → Actions)
| Secret | Esempio / Note |
|---|---|
| `WP_API_URL` | `https://cms.<dominio>/wp-json/edilmetal/v1` — API headless usata a build time **e** esposta al browser (`NEXT_PUBLIC_WP_API_URL`) per il POST `/lead` del form. **Obbligatorio** |
| `SITE_URL` | `https://<dominio>` — usato per canonical/sitemap/hreflang/OG |
| `PLESK_SFTP_HOST` | host SFTP Plesk (spesso il dominio o l'IP del server) |
| `PLESK_SFTP_PORT` | tipicamente `22` (SFTP su SSH) |
| `PLESK_SFTP_USER` | utente FTP/SFTP dell'abbonamento Plesk |
| `PLESK_SFTP_PASSWORD` | password dell'utente SFTP (oppure usa `PLESK_SSH_KEY`) |
| `PLESK_SSH_KEY` | (alternativa alla password) chiave privata SSH dedicata, se l'hosting espone SSH |
| `PLESK_REMOTE_PATH` | document root del dominio, es. `httpdocs/` (Plesk) o `httpdocs/anteprima/` per una preview |

> I segreti stanno SOLO nei GitHub Secrets. Mai nel repo, mai in `wp-config` versionato.

## 2. Costanti WordPress (in `wp-config.php`, NON versionato)
Vedi `cms/config/wp-config-snippet.php`. Necessarie per il webhook di rebuild:
```php
define( 'EDILMETAL_GH_REPO',  'mattiafilosa22/edilmetal' );
define( 'EDILMETAL_GH_TOKEN', '***PAT con scope repo/dispatch***' ); // GitHub Personal Access Token
```
Il webhook (mu-plugin `edilmetal-core/webhook`) invia un `repository_dispatch` con event type `wp-content-updated`, con debounce di 120s.

## 3. Accesso SFTP/SSH Plesk
- **SFTP (default Plesk)**: l'abbonamento fornisce host/utente/password FTP. La pipeline usa `lftp`/`SamKirkland/FTP-Deploy-Action` per sincronizzare `web/out/` nel document root.
- **SSH+rsync (se disponibile)**: alcuni piani Plesk espongono SSH → più efficiente. Genera una coppia dedicata `ssh-keygen -t ed25519 -f deploy_edilmetal`, aggiungi la pubblica alle authorized_keys dell'utente, metti la privata in `PLESK_SSH_KEY`.

## 4. Anteprima protetta (per far vedere la bozza al cliente)
Per mostrare il sito in una situazione realistica prima del go-live: pubblicare l'export in una sottocartella (es. `httpdocs/anteprima/`) protetta da **basic auth** (`.htaccess` + `.htpasswd`). Vedi `scripts/deploy-plesk.sh` e la voce `PLESK_REMOTE_PATH`.

## 5. DNS
- Dominio principale → document root Plesk (dove viene caricato `web/out/`).
- Sottodominio `cms.<dominio>` → WordPress su Plesk (PHP + MySQL), SSL Let's Encrypt.
