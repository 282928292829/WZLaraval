#!/usr/bin/env bash
# deploy/deploy.sh
# Run on the server as deploy user (or with sudo -u www-data):
#   bash /var/www/wasetzon/deploy/deploy.sh
#
# On first deploy: pass --fresh flag to also run seeders
#   bash /var/www/wasetzon/deploy/deploy.sh --fresh

set -euo pipefail

DEPLOY_PATH="/var/www/wasetzon"
PHP="php8.3"
ARTISAN="${DEPLOY_PATH}/artisan"
FRESH=${1:-}

echo "========================================"
echo "  Wasetzon — Deploy $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"

cd "${DEPLOY_PATH}"

# ─── 1. Put site in maintenance mode ─────────────────────────────────────────
echo "→ Enabling maintenance mode..."
${PHP} "${ARTISAN}" down --secret="wasetzon-deploy-bypass" --render="errors::503" || true

# ─── 2. Pull latest code ──────────────────────────────────────────────────────
echo "→ Pulling latest code from GitHub..."
git pull origin main

# ─── 3. Install/update PHP dependencies ──────────────────────────────────────
echo "→ Installing Composer dependencies (production)..."
composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --quiet

# ─── 4. Install/build frontend assets ────────────────────────────────────────
echo "→ Installing npm packages..."
npm ci --prefer-offline --silent

echo "→ Building frontend assets..."
npm run build

# ─── 5. Clear and re-cache config/routes/views ────────────────────────────────
echo "→ Caching config, routes, views..."
${PHP} "${ARTISAN}" config:cache
${PHP} "${ARTISAN}" route:cache
${PHP} "${ARTISAN}" view:cache
${PHP} "${ARTISAN}" event:cache

# ─── 6. Run database migrations ───────────────────────────────────────────────
echo "→ Running migrations..."
${PHP} "${ARTISAN}" migrate --force

# ─── 7. Seed (first deploy only) ─────────────────────────────────────────────
if [[ "${FRESH}" == "--fresh" ]]; then
    echo "→ Seeding roles, permissions, settings..."
    ${PHP} "${ARTISAN}" db:seed --class=RoleAndPermissionSeeder --force
    ${PHP} "${ARTISAN}" db:seed --class=SettingsSeeder --force
fi

# ─── 8. Storage symlink ───────────────────────────────────────────────────────
echo "→ Creating storage symlink..."
${PHP} "${ARTISAN}" storage:link --force

# ─── 9. File permissions ─────────────────────────────────────────────────────
echo "→ Setting permissions..."
chown -R www-data:www-data "${DEPLOY_PATH}/storage" "${DEPLOY_PATH}/bootstrap/cache"
chmod -R 775 "${DEPLOY_PATH}/storage" "${DEPLOY_PATH}/bootstrap/cache"

# ─── 10. Restart queue workers ────────────────────────────────────────────────
echo "→ Restarting queue workers..."
${PHP} "${ARTISAN}" queue:restart
supervisorctl restart wasetzon-worker:* 2>/dev/null || true

# ─── 11. Warm up Filament cache ───────────────────────────────────────────────
echo "→ Warming up Filament cache..."
${PHP} "${ARTISAN}" filament:cache-components 2>/dev/null || true

# ─── 12. Flush Redis cache (not sessions) ─────────────────────────────────────
echo "→ Flushing application cache (Redis, cache store only)..."
${PHP} "${ARTISAN}" cache:clear

# ─── 13. Bring site back up ───────────────────────────────────────────────────
echo "→ Disabling maintenance mode..."
${PHP} "${ARTISAN}" up

echo ""
echo "✓ Deploy complete — $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================"
