#!/usr/bin/env bash
# Rebuild + deploy del frontend statico su Plesk via SFTP (lftp mirror).
# Builda l'export dai contenuti WordPress (API headless) e lo pubblica nel
# document root del dominio. Lanciare dopo aver modificato i contenuti in WP.
#
# Uso:  ./scripts/deploy-plesk.sh
#
# Configurazione: copia scripts/.plesk.env.example in scripts/.plesk.env
# (NON tracciato) e compilalo. Richiede: lftp, node.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
[ -f "$SCRIPT_DIR/.plesk.env" ] && source "$SCRIPT_DIR/.plesk.env"

: "${WP_API_URL:?Imposta WP_API_URL (es. https://cms.<dominio>/wp-json/edilmetal/v1)}"
: "${SITE_URL:?Imposta SITE_URL (es. https://<dominio>)}"
: "${PLESK_SFTP_HOST:?Imposta PLESK_SFTP_HOST}"
: "${PLESK_SFTP_USER:?Imposta PLESK_SFTP_USER}"
: "${PLESK_SFTP_PASSWORD:?Imposta PLESK_SFTP_PASSWORD}"
: "${PLESK_REMOTE_PATH:?Imposta PLESK_REMOTE_PATH (es. httpdocs/ o httpdocs/anteprima/)}"
PLESK_SFTP_PORT="${PLESK_SFTP_PORT:-22}"

command -v lftp >/dev/null || { echo "lftp non installato (brew install lftp)"; exit 1; }

echo "▸ Build export statico ..."
( cd "$ROOT_DIR/web" \
  && WP_API_URL="$WP_API_URL" \
     NEXT_PUBLIC_WP_API_URL="$WP_API_URL" \
     NEXT_PUBLIC_SITE_URL="$SITE_URL" \
     npm run build )

echo "▸ Pubblico su Plesk ($PLESK_REMOTE_PATH) ..."
lftp -u "$PLESK_SFTP_USER","$PLESK_SFTP_PASSWORD" -p "$PLESK_SFTP_PORT" "sftp://$PLESK_SFTP_HOST" <<LFTP
set sftp:auto-confirm yes
set net:max-retries 2
mirror --reverse --delete --verbose --parallel=4 \
  --exclude-glob wp-content/ --exclude-glob .htpasswd --exclude-glob .htaccess \
  "$ROOT_DIR/web/out/" "$PLESK_REMOTE_PATH"
bye
LFTP

echo "✓ Deploy completato."
