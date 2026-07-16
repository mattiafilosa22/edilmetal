#!/usr/bin/env bash
# Setup ambiente locale: installa WordPress, i plugin free necessari e (se disponibile) esegue il seeder.
# Idempotente: si può rilanciare senza rompere nulla.
# Uso: ./scripts/dev-setup.sh   (dopo `docker compose up -d`)
set -euo pipefail
cd "$(dirname "$0")/.."

# Carica variabili da .env se presente, altrimenti usa i default.
if [ -f .env ]; then set -a; . ./.env; set +a; fi
WP_PORT="${WP_PORT:-8890}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-dev@edilmetal.local}"
WP_TITLE="${WP_TITLE:-Edilmetal (locale)}"

wp() { docker compose run --rm wpcli wp "$@"; }

echo "▸ Attendo che WordPress risponda su http://localhost:${WP_PORT} ..."
for i in $(seq 1 40); do
  if curl -sf -o /dev/null "http://localhost:${WP_PORT}/wp-login.php"; then break; fi
  sleep 3
done

echo "▸ Installazione core WordPress (se necessario) ..."
if ! wp core is-installed >/dev/null 2>&1; then
  wp core install \
    --url="http://localhost:${WP_PORT}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
else
  echo "  già installato."
fi

echo "▸ Lingua italiana ..."
wp language core install it_IT --activate || true

echo "▸ Plugin free necessari (Meta Box, Polylang, Fluent Forms, Complianz) ..."
wp plugin install meta-box polylang fluentform complianz-gdpr --activate || true

echo "▸ Permalink /%postname%/ (necessari per la REST) ..."
wp rewrite structure '/%postname%/' --hard || true
wp rewrite flush --hard || true

echo "▸ Seeder contenuti (se il plugin edilmetal-core è pronto) ..."
if wp edilmetal seed 2>/dev/null; then
  echo "  seeder eseguito."
else
  echo "  ⚠ seeder non disponibile (comando 'wp edilmetal seed' assente): il plugin edilmetal-core è ancora in sviluppo. Rilancia questo script più tardi."
fi

echo ""
echo "✅ Pronto."
echo "   WP admin  → http://localhost:${WP_PORT}/wp-admin  (${WP_ADMIN_USER} / ${WP_ADMIN_PASSWORD})"
echo "   API       → http://localhost:${WP_PORT}/wp-json/edilmetal/v1/settings"
echo "   phpMyAdmin→ http://localhost:${PMA_PORT:-8891}"
