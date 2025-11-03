#!/usr/bin/env bash
set -euo pipefail

WP_CONTAINER_NAME="${WP_CONTAINER_NAME:-united-payment-woocommerce}"
DB_CONTAINER_NAME="${DB_CONTAINER_NAME:-united-payment-woocommerce-db}"
MYSQL_USER="${MYSQL_USER:-wordpress}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-wordpress}"
MYSQL_DB="${MYSQL_DB:-wordpress}"

PLUGIN_SLUG="${PLUGIN_SLUG:-optimisthub-united-payment-for-woocommerce}"
WP_URL="${WP_URL:-http://localhost:8080}"
WP_TITLE="${WP_TITLE:-United Payment}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASS="${WP_ADMIN_PASS:-admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@unitedpayment.com}"

echo "[setup] WP container: $WP_CONTAINER_NAME"
echo "[setup] DB container: $DB_CONTAINER_NAME"

if ! docker ps --format '{{.Names}}' | grep -qx "$DB_CONTAINER_NAME"; then
  echo "[setup] DB container '$DB_CONTAINER_NAME' not found"
  exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$WP_CONTAINER_NAME"; then
  echo "[setup] WP container '$WP_CONTAINER_NAME' not found"
  exit 1
fi

echo "[setup] Waiting for MySQL..."
until docker exec "$DB_CONTAINER_NAME" bash -c "mysql -h ${DB_CONTAINER_NAME} -u${MYSQL_USER} -p${MYSQL_PASSWORD} -e 'SELECT 1' >/dev/null 2>&1"; do
  printf '.'
  sleep 2
done
echo && echo "[setup] MySQL is ready"

echo "[setup] Checking WP-CLI..."
docker exec "$WP_CONTAINER_NAME" bash -lc "command -v wp >/dev/null 2>&1 || ( \
  echo '[setup] Installing WP-CLI...'; \
  curl -s -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
  chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp )"

echo "[setup] Checking WP core files..."
if ! docker exec "$WP_CONTAINER_NAME" bash -c "test -d /var/www/html/wp-admin"; then
  echo "[setup] Downloading WordPress core..."
  docker exec "$WP_CONTAINER_NAME" bash -c "wp core download --allow-root"
else
  echo "[setup] WordPress core already present"
fi

echo "[setup] Checking wp-config.php..."
if ! docker exec "$WP_CONTAINER_NAME" bash -c "test -f /var/www/html/wp-config.php"; then
  echo "[setup] Generating wp-config.php..."
  docker exec "$WP_CONTAINER_NAME" bash -c "wp config create \
    --dbname=${MYSQL_DB} \
    --dbuser=${MYSQL_USER} \
    --dbpass=${MYSQL_PASSWORD} \
    --dbhost=${DB_CONTAINER_NAME} \
    --skip-check \
    --allow-root"
else
  echo "[setup] wp-config.php already exists"
fi

if docker exec "$WP_CONTAINER_NAME" bash -c "wp core is-installed --allow-root >/dev/null 2>&1"; then
  echo "[setup] WordPress already installed"
else
  echo "[setup] Installing WordPress..."
  docker exec "$WP_CONTAINER_NAME" bash -c "wp core install \
    --url='${WP_URL}' \
    --title='${WP_TITLE}' \
    --admin_user='${WP_ADMIN_USER}' \
    --admin_password='${WP_ADMIN_PASS}' \
    --admin_email='${WP_ADMIN_EMAIL}' \
    --skip-email \
    --allow-root"
fi

if docker exec "$WP_CONTAINER_NAME" bash -c "wp plugin is-active woocommerce --allow-root >/dev/null 2>&1"; then
  echo "[setup] WooCommerce is already active"
else
  echo "[setup] Installing and activating WooCommerce..."
  docker exec "$WP_CONTAINER_NAME" bash -c "wp plugin install woocommerce --activate --allow-root"
fi

docker exec "$WP_CONTAINER_NAME" bash -c "wp option update woocommerce_onboarding_profile '{\"skipped\":true}' --format=json --allow-root >/dev/null 2>&1 || true"
docker exec "$WP_CONTAINER_NAME" bash -c "wp option update woocommerce_setup_just_completed yes --allow-root >/dev/null 2>&1 || true"
docker exec "$WP_CONTAINER_NAME" bash -c "wp option update woocommerce_admin_install_timestamp $(date +%s) --allow-root >/dev/null 2>&1 || true"

docker exec "$WP_CONTAINER_NAME" bash -c "wp option update woocommerce_currency GEL --allow-root >/dev/null 2>&1 || true"

if docker exec "$WP_CONTAINER_NAME" bash -c "wp plugin is-active ${PLUGIN_SLUG} --allow-root >/dev/null 2>&1"; then
  echo "[setup] Plugin '${PLUGIN_SLUG}' already active"
else
  echo "[setup] Activating plugin '${PLUGIN_SLUG}'..."
  docker exec "$WP_CONTAINER_NAME" bash -c "wp plugin activate ${PLUGIN_SLUG} --allow-root"
fi

if docker exec "$WP_CONTAINER_NAME" bash -c "wp post list --post_type=product --allow-root --format=count | grep -q '^0$'"; then
  echo "[setup] Importing sample products..."
  docker exec "$WP_CONTAINER_NAME" bash -c "wp plugin install wordpress-importer --activate --allow-root"
  docker exec "$WP_CONTAINER_NAME" bash -c "wp import /var/www/html/wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip --allow-root"
else
  echo "[setup] Products already exist, skipping import"
fi

# Enable development flag only (debug already handled via ENV)
docker exec "$WP_CONTAINER_NAME" bash -c "wp config set WP_ENVIRONMENT_TYPE development --allow-root"

# Set permalink structure and flush
docker exec "$WP_CONTAINER_NAME" bash -c "wp rewrite structure '/%postname%/' --allow-root"
docker exec "$WP_CONTAINER_NAME" bash -c "wp rewrite flush --hard --allow-root"

# Install WooCommerce default pages (Cart, Checkout, etc)
docker exec "$WP_CONTAINER_NAME" bash -c "wp wc tool run install_pages --user=1 --allow-root"

# Set file permissions
docker exec "$WP_CONTAINER_NAME" bash -c "chmod -R 777 /var/www/html/wp-content/uploads"
docker exec "$WP_CONTAINER_NAME" bash -c "chmod -R 777 /var/www/html/wp-content/upgrade"
docker exec "$WP_CONTAINER_NAME" bash -c "chmod -R 777 /var/www/html/wp-content/uploads/wc-logs"

echo "[setup] Setup completed"
