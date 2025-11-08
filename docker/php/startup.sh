#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Composer auth (Marketplace keys)
if [[ -n "${MAGENTO_PUBLIC_KEY:-}" && -n "${MAGENTO_PRIVATE_KEY:-}" ]]; then
  echo "==> Configuring Composer auth for repo.magento.com"
  composer config --global http-basic.repo.magento.com "${MAGENTO_PUBLIC_KEY}" "${MAGENTO_PRIVATE_KEY}"
fi

# Create project if composer.json missing (first boot)
if [[ ! -f composer.json ]]; then
  echo "==> Creating Magento project ${MAGENTO_VERSION:-2.4.7}"
  export COMPOSER_MEMORY_LIMIT="${COMPOSER_MEMORY_LIMIT:--1}"
  composer create-project --repository=https://repo.magento.com/ \
    "magento/project-community-edition=${MAGENTO_VERSION:-2.4.7}" .
fi

# Basic permissions
echo "==> Fixing permissions"
chown -R www-data:www-data .
find var generated pub/static pub/media app/etc -type f -exec chmod g+w {} \; || true
find var generated pub/static pub/media app/etc -type d -exec chmod g+ws {} \; || true

# Install if not installed
if [[ ! -f app/etc/env.php ]]; then
  echo "==> Running setup:install"
  php -d memory_limit="${PHP_MEMORY_LIMIT:-2G}" bin/magento setup:install \
    --base-url="${BASE_URL:-http://localhost:8081/}" \
    --db-host="${DB_HOST:-mysql}" \
    --db-name="${DB_NAME:-magento}" \
    --db-user="${DB_USER:-magento}" \
    --db-password="${DB_PASSWORD:-magento}" \
    --admin-firstname="${ADMIN_FIRSTNAME:-Admin}" \
    --admin-lastname="${ADMIN_LASTNAME:-User}" \
    --admin-email="${ADMIN_EMAIL:-admin@example.com}" \
    --admin-user="${ADMIN_USER:-admin}" \
    --admin-password="${ADMIN_PASSWORD:-Admin123!}" \
    --language="${LANGUAGE:-en_US}" \
    --currency="${CURRENCY:-USD}" \
    --timezone="${TIMEZONE:-America/Chicago}" \
    --use-rewrites=1 \
    --cache-backend=redis \
    --cache-backend-redis-server="${REDIS_HOST:-redis}" \
    --cache-backend-redis-db="${CACHE_REDIS_DB:-0}" \
    --page-cache=redis \
    --page-cache-redis-server="${REDIS_HOST:-redis}" \
    --page-cache-redis-db="${PAGE_CACHE_REDIS_DB:-1}" \
    --session-save=redis \
    --session-save-redis-host="${REDIS_HOST:-redis}" \
    --session-save-redis-db="${SESSION_REDIS_DB:-2}" \
    --search-engine=elasticsearch \
    --elasticsearch-host="${ES_HOST:-elasticsearch}" \
    --elasticsearch-port="${ES_PORT:-9200}"
fi

# Developer/production mode
if [[ "${MAGENTO_MODE:-developer}" == "developer" ]]; then
  php -d memory_limit="${PHP_MEMORY_LIMIT:-2G}" bin/magento deploy:mode:set developer || true
fi

php -d memory_limit="${PHP_MEMORY_LIMIT:-2G}" bin/magento setup:upgrade || true
# Warm up
php -d memory_limit="${PHP_MEMORY_LIMIT:-2G}" bin/magento cache:flush || true
php -d memory_limit="${PHP_MEMORY_LIMIT:-2G}" bin/magento indexer:reindex || true

echo "==> Startup finished; starting php-fpm"
exec php-fpm
